{{--
    A clickable pengurus entry in the struktur organisasi chart. Expects: $pengurus (a row from
    OrganizationUnitController::activePengurus(), with user_id/user_name/jabatan/email/photo/
    whatsapp_url), $class (the CSS class controlling its look: org-node-person or org-leaf-person),
    $canManageTasks (whether the "Beri Tugas" action should be offered).
--}}
@php
    $detail = [
        'name' => $pengurus->user_name,
        'jabatan' => $pengurus->jabatan ?? 'Tanpa jabatan',
        'email' => $pengurus->email,
        'whatsappUrl' => $pengurus->whatsapp_url,
        'photo' => $pengurus->photo ? route('users.photo', $pengurus->user_id) : null,
        'taskUrl' => $canManageTasks ? route('tasks.index', ['assign_to' => $pengurus->user_id]) : null,
    ];
@endphp
<button type="button" class="{{ $class }} block w-full cursor-pointer text-left" data-person-detail="{{ json_encode($detail) }}">{{ $pengurus->user_name }}<span>{{ $pengurus->jabatan ?? 'Tanpa jabatan' }}</span></button>
