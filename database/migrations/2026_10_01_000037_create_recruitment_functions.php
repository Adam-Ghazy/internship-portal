<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. deny_mutation: tolak UPDATE/DELETE di tabel immutable
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION recruitment.deny_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'immutable table: %', TG_TABLE_NAME USING ERRCODE='23514'; END $$
SQL);

        DB::statement("CREATE TRIGGER immutable_submission BEFORE UPDATE OR DELETE ON recruitment.application_submissions FOR EACH ROW EXECUTE FUNCTION recruitment.deny_mutation()");
        DB::statement("CREATE TRIGGER immutable_submission_document BEFORE UPDATE OR DELETE ON recruitment.application_documents FOR EACH ROW EXECUTE FUNCTION recruitment.deny_mutation()");
        DB::statement("CREATE TRIGGER immutable_group_document BEFORE UPDATE OR DELETE ON recruitment.group_documents FOR EACH ROW EXECUTE FUNCTION recruitment.deny_mutation()");
        DB::statement("CREATE TRIGGER immutable_publication BEFORE UPDATE OR DELETE ON recruitment.application_publications FOR EACH ROW EXECUTE FUNCTION recruitment.deny_mutation()");
        DB::statement("CREATE TRIGGER immutable_application_event BEFORE UPDATE OR DELETE ON recruitment.application_events FOR EACH ROW EXECUTE FUNCTION recruitment.deny_mutation()");
        DB::statement("CREATE TRIGGER immutable_audit_event BEFORE UPDATE OR DELETE ON recruitment.audit_events FOR EACH ROW EXECUTE FUNCTION recruitment.deny_mutation()");

        // 2. freeze_completed_review: review completed tidak bisa diubah/dihapus
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION recruitment.freeze_completed_review() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF OLD.status='completed' THEN RAISE EXCEPTION 'completed review immutable' USING ERRCODE='23514'; END IF;
 IF TG_OP='DELETE' THEN RETURN OLD; END IF;
 RETURN NEW;
END $$
SQL);
        DB::statement("CREATE TRIGGER freeze_review BEFORE UPDATE OR DELETE ON recruitment.application_reviews FOR EACH ROW EXECUTE FUNCTION recruitment.freeze_completed_review()");

        // 3. has_staff_scope: cek kepemilikan scope staff aktif
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION recruitment.has_staff_scope(p_user bigint, p_role text, p_unit bigint) RETURNS boolean
LANGUAGE sql STABLE SET search_path TO recruitment, pg_catalog AS $$
 SELECT EXISTS(SELECT 1 FROM staff_assignments s JOIN users u ON u.id=s.user_id
 WHERE s.user_id=p_user AND s.role_code=p_role AND s.org_unit_id IS NOT DISTINCT FROM p_unit
 AND s.valid_from<=CURRENT_TIMESTAMP AND (s.valid_to IS NULL OR CURRENT_TIMESTAMP<s.valid_to) AND u.disabled_at IS NULL)
$$
SQL);

        // 4. complete_review: transisi review manager -> sm -> decided
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION recruitment.complete_review(p_review bigint, p_actor bigint, p_outcome text, p_note text) RETURNS bigint
LANGUAGE plpgsql SET search_path TO recruitment, pg_catalog AS $$
DECLARE r application_reviews%ROWTYPE; a applications%ROWTYPE; v vacancies%ROWTYPE; m application_reviews%ROWTYPE; n bigint;
BEGIN
 SELECT * INTO STRICT r FROM application_reviews WHERE id=p_review;
 SELECT * INTO STRICT v FROM vacancies WHERE id=r.vacancy_id FOR UPDATE;
 SELECT * INTO STRICT a FROM applications WHERE id=r.application_id FOR UPDATE;
 SELECT * INTO STRICT r FROM application_reviews WHERE id=p_review FOR UPDATE;
 IF r.assignee_id<>p_actor OR NOT has_staff_scope(p_actor,r.stage,r.org_unit_id) OR NOT EXISTS(
 SELECT 1 FROM staff_assignments WHERE id=r.staff_assignment_id AND valid_from<=CURRENT_TIMESTAMP AND (valid_to IS NULL OR CURRENT_TIMESTAMP<valid_to))
 THEN RAISE EXCEPTION 'reviewer authorization denied' USING ERRCODE='42501'; END IF;
 IF r.status<>'pending' OR r.based_on_submission_id<>a.current_submission_id OR p_note IS NULL OR length(btrim(p_note))=0
 THEN RAISE EXCEPTION 'invalid review state' USING ERRCODE='23514'; END IF;
 IF r.stage='manager' THEN
  IF a.stage<>'manager_review' OR p_outcome IS NULL OR p_outcome NOT IN ('recommended','not_recommended') THEN RAISE EXCEPTION 'invalid manager transition' USING ERRCODE='23514'; END IF;
  IF NOT EXISTS(SELECT 1 FROM application_reviews WHERE application_id=a.id AND stage='sm' AND status='blocked' AND assignee_id<>r.assignee_id AND based_on_submission_id=r.based_on_submission_id)
  THEN RAISE EXCEPTION 'distinct SM on same submission required' USING ERRCODE='23514'; END IF;
  UPDATE application_reviews SET status='completed',outcome=p_outcome,note_internal=p_note,acted_at=clock_timestamp() WHERE id=r.id;
  UPDATE application_reviews SET status='pending' WHERE application_id=a.id AND stage='sm' AND status='blocked';
  UPDATE applications SET stage='sm_review',updated_at=clock_timestamp() WHERE id=a.id;
 ELSE
  SELECT * INTO m FROM application_reviews WHERE application_id=a.id AND stage='manager' AND status='completed';
  IF a.stage<>'sm_review' OR m.id IS NULL OR m.assignee_id=r.assignee_id OR m.based_on_submission_id<>r.based_on_submission_id
   OR p_outcome IS NULL OR p_outcome NOT IN ('accepted','rejected') THEN RAISE EXCEPTION 'Manager before SM required' USING ERRCODE='23514'; END IF;
  IF p_outcome='accepted' THEN
   SELECT count(*) INTO n FROM application_reviews WHERE vacancy_id=v.id AND stage='sm' AND status='completed' AND outcome='accepted';
   IF n>=v.quota THEN RAISE EXCEPTION 'quota exhausted' USING ERRCODE='23514'; END IF;
  END IF;
  UPDATE application_reviews SET status='completed',outcome=p_outcome,note_internal=p_note,acted_at=clock_timestamp() WHERE id=r.id;
  UPDATE applications SET stage='decided',updated_at=clock_timestamp() WHERE id=a.id;
 END IF;
 INSERT INTO application_events(application_id,actor_id,event_type) VALUES(a.id,p_actor,'review.completed');
 RETURN r.id;
END $$
SQL);

        // 5. publish_decision: admin publish hasil akhir
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION recruitment.publish_decision(p_application bigint, p_actor bigint, p_message text) RETURNS bigint
LANGUAGE plpgsql SET search_path TO recruitment, pg_catalog AS $$
DECLARE a applications%ROWTYPE; rid bigint; result bigint;
BEGIN
 SELECT * INTO STRICT a FROM applications WHERE id=p_application;
 PERFORM 1 FROM vacancies WHERE id=a.vacancy_id FOR UPDATE;
 SELECT * INTO STRICT a FROM applications WHERE id=p_application FOR UPDATE;
 IF NOT has_staff_scope(p_actor,'admin',NULL) THEN RAISE EXCEPTION 'admin required' USING ERRCODE='42501'; END IF;
 SELECT id INTO result FROM application_publications WHERE application_id=a.id;
 IF result IS NOT NULL THEN RETURN result; END IF;
 IF a.stage<>'decided' THEN RAISE EXCEPTION 'not decided' USING ERRCODE='23514'; END IF;
 SELECT id INTO STRICT rid FROM application_reviews WHERE application_id=a.id AND stage='sm' AND status='completed';
 INSERT INTO application_publications(application_id,final_review_id,published_by,public_message) VALUES(a.id,rid,p_actor,coalesce(p_message,'')) RETURNING id INTO result;
 INSERT INTO outbox_events(event_key,event_type,payload) VALUES('decision-published:'||result,'decision.published',jsonb_build_object('publication_id',result));
 INSERT INTO application_events(application_id,actor_id,event_type) VALUES(a.id,p_actor,'decision.published');
 RETURN result;
END $$
SQL);

        // 6. withdraw_application: pelamar menarik lamaran
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION recruitment.withdraw_application(p_application bigint, p_actor bigint) RETURNS bigint
LANGUAGE plpgsql SET search_path TO recruitment, pg_catalog AS $$
DECLARE a applications%ROWTYPE;
BEGIN
 SELECT * INTO STRICT a FROM applications WHERE id=p_application;
 PERFORM 1 FROM vacancies WHERE id=a.vacancy_id FOR UPDATE;
 SELECT * INTO STRICT a FROM applications WHERE id=p_application FOR UPDATE;
 IF a.applicant_user_id<>p_actor THEN RAISE EXCEPTION 'owner required' USING ERRCODE='42501'; END IF;
 IF a.stage='withdrawn' THEN RETURN a.id; END IF;
 IF a.stage IN ('draft','decided') THEN RAISE EXCEPTION 'cannot withdraw' USING ERRCODE='23514'; END IF;
 UPDATE application_reviews SET status='cancelled' WHERE application_id=a.id AND status IN ('blocked','pending');
 UPDATE application_revision_requests SET status='cancelled',cancelled_at=clock_timestamp() WHERE application_id=a.id AND status='open';
 UPDATE applications SET stage='withdrawn',withdrawn_at=clock_timestamp(),updated_at=clock_timestamp() WHERE id=a.id;
 INSERT INTO application_events(application_id,actor_id,event_type) VALUES(a.id,p_actor,'application.withdrawn');
 RETURN a.id;
END $$
SQL);

        // 7. change_quota: admin ubah kuota vacancy
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION recruitment.change_quota(p_vacancy bigint, p_actor bigint, p_quota integer) RETURNS void
LANGUAGE plpgsql SET search_path TO recruitment, pg_catalog AS $$
DECLARE v vacancies%ROWTYPE; cap integer; allocated bigint; accepted bigint;
BEGIN
 SELECT * INTO STRICT v FROM vacancies WHERE id=p_vacancy;
 SELECT requested_count INTO STRICT cap FROM internship_requests WHERE id=v.request_id FOR UPDATE;
 SELECT * INTO STRICT v FROM vacancies WHERE id=p_vacancy FOR UPDATE;
 IF NOT has_staff_scope(p_actor,'admin',NULL) THEN RAISE EXCEPTION 'admin required' USING ERRCODE='42501'; END IF;
 SELECT coalesce(sum(quota),0) INTO allocated FROM vacancies WHERE request_id=v.request_id AND id<>v.id;
 SELECT count(*) INTO accepted FROM application_reviews WHERE vacancy_id=v.id AND stage='sm' AND status='completed' AND outcome='accepted';
 IF p_quota IS NULL OR p_quota<=0 OR p_quota<accepted OR allocated+p_quota>cap THEN RAISE EXCEPTION 'quota allocation invalid' USING ERRCODE='23514'; END IF;
 UPDATE vacancies SET quota=p_quota WHERE id=v.id;
 INSERT INTO audit_events(resource_type,resource_id,actor_id,event_type,metadata) VALUES('vacancy',v.id,p_actor,'quota.changed',jsonb_build_object('old',v.quota,'new',p_quota));
END $$
SQL);

        // Revoke akses publik
        DB::statement("REVOKE ALL ON SCHEMA recruitment FROM PUBLIC");
        DB::statement("REVOKE ALL ON ALL TABLES IN SCHEMA recruitment FROM PUBLIC");
        DB::statement("REVOKE ALL ON ALL FUNCTIONS IN SCHEMA recruitment FROM PUBLIC");
    }

    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS immutable_submission ON recruitment.application_submissions");
        DB::statement("DROP TRIGGER IF EXISTS immutable_submission_document ON recruitment.application_documents");
        DB::statement("DROP TRIGGER IF EXISTS immutable_group_document ON recruitment.group_documents");
        DB::statement("DROP TRIGGER IF EXISTS immutable_publication ON recruitment.application_publications");
        DB::statement("DROP TRIGGER IF EXISTS immutable_application_event ON recruitment.application_events");
        DB::statement("DROP TRIGGER IF EXISTS immutable_audit_event ON recruitment.audit_events");
        DB::statement("DROP TRIGGER IF EXISTS freeze_review ON recruitment.application_reviews");
        DB::statement("DROP FUNCTION IF EXISTS recruitment.change_quota(bigint,bigint,integer)");
        DB::statement("DROP FUNCTION IF EXISTS recruitment.withdraw_application(bigint,bigint)");
        DB::statement("DROP FUNCTION IF EXISTS recruitment.publish_decision(bigint,bigint,text)");
        DB::statement("DROP FUNCTION IF EXISTS recruitment.complete_review(bigint,bigint,text,text)");
        DB::statement("DROP FUNCTION IF EXISTS recruitment.has_staff_scope(bigint,text,bigint)");
        DB::statement("DROP FUNCTION IF EXISTS recruitment.freeze_completed_review()");
        DB::statement("DROP FUNCTION IF EXISTS recruitment.deny_mutation()");
    }
};
