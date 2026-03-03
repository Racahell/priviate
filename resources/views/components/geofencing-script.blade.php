<script>
    document.addEventListener('DOMContentLoaded', function() {
        const startSessionBtn = document.getElementById('start-session-btn');
        const statusMsg = document.getElementById('geo-status');

        if (startSessionBtn) {
            startSessionBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!navigator.geolocation) {
                    statusMsg.innerHTML = '<span class="text-danger">Geolocation is not supported by your browser</span>';
                    return;
                }

                statusMsg.innerHTML = '<span class="text-info"><i class="fa fa-spinner fa-spin"></i> Verifying Location...</span>';
                startSessionBtn.disabled = true;

                navigator.geolocation.getCurrentPosition(success, error, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });

                function success(position) {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    // Send to Backend
                    fetch('/api/tutor/session/start', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            latitude: latitude,
                            longitude: longitude,
                            accuracy: accuracy,
                            session_id: startSessionBtn.dataset.sessionId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.allowed) {
                            statusMsg.innerHTML = '<span class="text-success"><i class="fa fa-check"></i> Session Started!</span>';
                            // Reload or Redirect
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            statusMsg.innerHTML = `<span class="text-danger"><i class="fa fa-times"></i> ${data.message} (${Math.round(data.distance)}m away)</span>`;
                            startSessionBtn.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        statusMsg.innerHTML = '<span class="text-danger">Server Error. Please try again.</span>';
                        startSessionBtn.disabled = false;
                    });
                }

                function error(err) {
                    console.warn(`ERROR(${err.code}): ${err.message}`);
                    statusMsg.innerHTML = '<span class="text-danger">Unable to retrieve location. Please enable GPS.</span>';
                    startSessionBtn.disabled = false;
                }
            });
        }
    });
</script>
