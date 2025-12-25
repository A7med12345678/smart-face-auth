@extends('layouts.app') {{-- Assume app layout or user can customize via publishing --}}

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <div class="text-center mb-4 pt-5">
        <a href="{{ route('face.register.form') }}" class="btn btn-outline-primary rounded-pill px-4">
            <i class="fas fa-user-plus me-2"></i> تسجيل بصمة وجه جديدة
        </a>
    </div>

    <hr class="w-50 mx-auto mt-5">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden"
                    style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <div class="d-inline-block p-3 rounded-circle bg-primary bg-opacity-10 mb-3">
                                <i class="fas fa-user-shield fa-3x text-primary"></i>
                            </div>
                            <h3 class="fw-bold mb-2">تسجيل الدخول الذكي</h3>
                            <p class="text-muted">استخدم بصمة الوجه للوصول السريع لحسابك</p>
                        </div>

                        {{-- Scanner Display --}}
                        <div class="face-scanner-container position-relative mx-auto mb-4"
                            style="width: 280px; height: 280px;">
                            <div class="scanner-ring"></div>
                            <div class="scanner-line"></div>

                            <video id="video" width="280" height="280" autoplay muted
                                class="rounded-circle shadow-sm" style="object-fit: cover; border: 4px solid #fff;"></video>
                            <canvas id="overlay" width="280" height="280"
                                class="position-absolute top-0 start-0 rounded-circle"></canvas>
                        </div>

                        <div id="status-badge" class="badge rounded-pill bg-secondary px-4 py-2 mb-4">
                            جاري تهيئة النظام...
                        </div>

                        <div class="d-grid gap-2">
                            <button id="capture-btn" class="btn btn-primary btn-lg rounded-pill shadow-sm py-3 fw-bold"
                                onclick="captureFace()" disabled>
                                <i class="fas fa-camera me-2"></i> التقاط البصمة
                            </button>
                            <a href="{{ route('login') }}" class="btn btn-link text-decoration-none text-muted">العودة
                                لتسجيل الدخول التقليدي</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .face-scanner-container {
            border-radius: 50%;
            padding: 5px;
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            position: relative;
        }

        .scanner-ring {
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border: 2px solid #0d6efd;
            border-radius: 50%;
            animation: pulse-ring 2s infinite;
            z-index: 1;
            pointer-events: none;
        }

        .scanner-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: rgba(13, 110, 253, 0.5);
            box-shadow: 0 0 15px #0d6efd;
            z-index: 3;
            animation: scan-move 3s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 1; }
            100% { transform: scale(1.1); opacity: 0; }
        }

        @keyframes scan-move {
            0%, 100% { top: 10%; }
            50% { top: 90%; }
        }

        #video { transform: scaleX(-1); }
        #overlay { transform: scaleX(-1); }
    </style>

    <script>
        const MODEL_URL = '/face-api-login/models';
        const video = document.getElementById('video');
        const overlay = document.getElementById('overlay');
        const statusBadge = document.getElementById('status-badge');
        const captureBtn = document.getElementById('capture-btn');

        let modelsLoaded = false;
        let isProcessing = false;

        async function init() {
            try {
                updateStatus('جاري تحميل النماذج...', 'secondary');
                await loadModels();
                updateStatus('جاري تشغيل الكاميرا...', 'info');
                await startCamera();
                updateStatus('جاهز للتعرف', 'success');
                captureBtn.disabled = false;
                processVideo();
            } catch (err) {
                console.error("Initialization failed:", err);
                updateStatus('فشل في الوصول للكاميرا', 'danger');
            }
        }

        async function loadModels() {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);
            modelsLoaded = true;
        }

        async function startCamera() {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 320, height: 320, frameRate: { ideal: 20 } }
            });
            video.srcObject = stream;
            return new Promise(resolve => video.onloadedmetadata = () => resolve());
        }

        async function processVideo() {
            if (modelsLoaded && video.readyState === 4 && !isProcessing) {
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
                    inputSize: 160
                }));
                const context = overlay.getContext('2d');
                context.clearRect(0, 0, overlay.width, overlay.height);
                if (detection) {
                    const dims = faceapi.matchDimensions(overlay, video, true);
                    const resizedDetection = faceapi.resizeResults(detection, dims);
                    const box = resizedDetection.box;
                    context.strokeStyle = '#00ff00';
                    context.lineWidth = 2;
                    context.strokeRect(box.x, box.y, box.width, box.height);
                }
            }
            requestAnimationFrame(processVideo);
        }

        function updateStatus(text, type) {
            statusBadge.innerText = text;
            statusBadge.className = `badge rounded-pill px-4 py-2 mb-4 bg-${type} text-white`;
        }

        async function captureFace() {
            if (!modelsLoaded || isProcessing) return;
            isProcessing = true;
            captureBtn.disabled = true;
            captureBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري التحقق...';
            updateStatus('جاري مطابقة البيانات...', 'warning');

            try {
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
                    inputSize: 224
                })).withFaceLandmarks().withFaceDescriptor();

                if (!detection) {
                    updateStatus('لم يتم اكتشاف وجه بوضوح', 'danger');
                    resetCaptureButton();
                    return;
                }

                const res = await fetch("{{ route('face.login.submit') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ descriptor: Array.from(detection.descriptor) })
                });

                const data = await res.json();
                if (data.success) {
                    updateStatus('تم التعرف بنجاح! جاري الدخول...', 'success');
                    setTimeout(() => window.location.href = data.redirect || "/", 500);
                } else {
                    updateStatus('عذراً، لم يتم التعرف على الحساب', 'danger');
                    resetCaptureButton();
                }
            } catch (err) {
                console.error(err);
                updateStatus('حدث خطأ في الاتصال', 'danger');
                resetCaptureButton();
            }
        }

        function resetCaptureButton() {
            isProcessing = false;
            captureBtn.disabled = false;
            captureBtn.innerHTML = '<i class="fas fa-camera me-2"></i> التقاط البصمة';
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
@endsection
