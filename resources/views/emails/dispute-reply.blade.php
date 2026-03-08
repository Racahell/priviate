<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balasan Kritik</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2a44; line-height: 1.6;">
    <p>Halo {{ $recipientName ?? 'Pengguna' }},</p>

    <p>Kritik Anda telah mendapatkan balasan dari admin.</p>

    <p><strong>Nomor Kritik:</strong> #{{ $dispute->id }}</p>
    <p><strong>Status:</strong> {{ $status }}</p>
    <p><strong>Alasan:</strong> {{ $dispute->reason }}</p>

    <p><strong>Balasan Admin:</strong></p>
    <p>{{ $notes }}</p>

    @if(!empty($actorName))
        <p><strong>Dibalas oleh:</strong> {{ $actorName }}</p>
    @endif

    <p>Terima kasih.</p>
</body>
</html>
