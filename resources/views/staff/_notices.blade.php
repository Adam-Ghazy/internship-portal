@if (session('status'))
    <div role="status" class="mt-6 rounded-card border border-line bg-surface p-4 text-sm">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div id="staff-errors" role="alert" tabindex="-1" class="mt-6 rounded-card border border-primary bg-primary-soft p-4 text-sm text-primary-hover">
        <h2 class="font-semibold">Tindakan belum dapat diproses</h2>
        <ul class="mt-2 list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <p class="mt-3">Periksa isian dan kewenangan Anda, lalu coba kembali.</p>
    </div>
@endif
