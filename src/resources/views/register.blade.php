@extends('layouts.app')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden"
                    style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <div class="d-inline-block p-3 rounded-circle bg-success bg-opacity-10 mb-3">
                                <i class="fas fa-face-smile fa-3x text-success"></i>
                            </div>
                            <h3 class="fw-bold mb-2">تسجيل بصمة الوجه</h3>
                            <p class="text-muted">قم بتأمين حسابك باستخدام تقنية التعرف على الوجه</p>
                        </div>

                        <div class="row mb-4 align-items-center justify-content-center">
                            <div class="col-md-8">
                                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                                    <span class="input-group-text bg-white border-0"><i
                                            class="fas fa-id-card text-muted"></i></span>
                                    <input type="text" id="center_code" class="form-control border-0"
                                        placeholder="ادخل كودك بالمنصة أولًا">
                                </div>
                            </div>
                        </div>

                        {{-- Scanner --}}
                        <div class="face-scanner-container position-relative mx-auto mb-4"
                            style="width: 300px; height: 300px;">
                            <div class="scanner-ring"></div>
                            <video id="video" width="300" height="300" autoplay muted
                                class="rounded-circle shadow-sm" style="object-fit: cover; border: 4px solid #fff;"></video>
                            <canvas id="overlay" width="300" height="300"
                                class="position-absolute top-0 start-0 rounded-circle"></canvas>
                        </div>

                        <div class="mb-4">
                            <div id="status-badge" class="badge rounded-pill bg-secondary px-4 py-2 mb-2">
                                جاري التهيئة...
                            </div>
                            <div class="d-flex justify-content-center gap-2 mt-2">
                                <span class="badge bg-light text-dark shadow-sm px-3 py-2 border">
                                    <i class="fas fa-images me-1 text-primary"></i>
                                    <span id="counter">0</span> بصمات محفوظة
                                </span>
                            </div>
                        </div>

                        <div class="d-grid gap-2 col-md-8 mx-auto">
                            <button id="capture-btn" class="btn btn-success btn-lg rounded-pill shadow-sm py-3 fw-bold"
                                onclick="captureMultipleAndSave()" disabled>
                                <i class="fas fa-user-plus me-2"></i> حفظ بصمة جديدة
                            </button>
                        </div>

                        <p class="mt-4 text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            سيتم التقاط 3 زوايا مختلفة تلقائياً لضمان دقة التعرف
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .face-scanner-container {
            border-radius: 50%;
            padding: 5px;
            background: linear-gradient(45deg, #198754, #20c997);
            position: relative;
        }

        .scanner-ring {
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border: 2px solid #198754;
            border-radius: 50%;
            animation: pulse-ring 2s infinite;
            z-index: 1;
            pointer-events: none;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                opacity: 1;
            }

            100% {
                transform: scale(1.1);
                opacity: 0;
            }
        }

        #video {
            transform: scaleX(-1);
        }

        .form-control:focus {
            box-shadow: none;
        }
    </style>

    <script>
        const MODEL_URL = '/face-api-login/models';
        let modelsLoaded = false;
        let savedCount = 0;
        const video = document.getElementById('video');
        const overlay = document.getElementById('overlay');
        const counterEl = document.getElementById('counter');
        const statusBadge = document.getElementById('status-badge');
        const captureBtn = document.getElementById('capture-btn');

        async function init() {
            try {
                updateStatus('جاري تحميل النماذج...', 'secondary');
                await loadModels();
                updateStatus('جاري تشغيل الكاميرا...', 'info');
                await startCamera();
                updateStatus('جاهز للالتقاط', 'success');
                captureBtn.disabled = false;

                setInterval(async () => {
                    if (modelsLoaded && video.readyState === 4) {
                        try {
                            const detection = await faceapi.detectSingleFace(video, new faceapi
                                .TinyFaceDetectorOptions());
                            const context = overlay.getContext('2d');
                            context.clearRect(0, 0, overlay.width, overlay.height);
                            if (detection) {
                                const dims = faceapi.matchDimensions(overlay, video, true);
                                const resizedDetection = faceapi.resizeResults(detection, dims);
                                const box = resizedDetection.box;
                                context.strokeStyle = '#00ff00';
                                context.lineWidth = 4;
                                context.strokeRect(box.x, box.y, box.width, box.height);
                            }
                        } catch (e) {
                            console.warn(e);
                        }
                    }
                }, 100);
            } catch (err) {
                console.error(err);
                updateStatus('حدث خطأ في التشغيل', 'danger');
            }
        }

        function updateStatus(text, type) {
            statusBadge.innerText = text;
            statusBadge.className = `badge rounded-pill px-4 py-2 mb-2 bg-${type} text-white`;
        }

        async function loadModels() {
            if (modelsLoaded) return;
            await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            modelsLoaded = true;
        }

        async function startCamera() {
            if (!video.srcObject) {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: 300,
                        height: 300
                    }
                });
                video.srcObject = stream;
            }
            return new Promise(resolve => video.onloadedmetadata = () => resolve());
        }

        async function captureAndSaveSingle() {
            const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks().withFaceDescriptor();
            return detection ? detection.descriptor : null;
        }

        async function captureMultipleAndSave() {
            const centerCode = document.getElementById('center_code').value;
            if (!centerCode) {
                alert('الرجاء إدخال كود المركز أولاً');
                return;
            }

            const descriptors = [];
            captureBtn.disabled = true;
            captureBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري الالتقاط...';

            try {
                for (let i = 0; i < 3; i++) {
                    updateStatus(`جاري التقاط صورة ${i + 1} من 3...`, 'warning');
                    let descriptor = null;
                    let attempts = 0;
                    while (!descriptor && attempts < 5) {
                        descriptor = await captureAndSaveSingle();
                        attempts++;
                        if (!descriptor) await new Promise(r => setTimeout(r, 300));
                    }
                    if (!descriptor) {
                        updateStatus('تعذر التقاط الوجه', 'danger');
                        captureBtn.disabled = false;
                        captureBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i> حفظ بصمة جديدة';
                        return;
                    }
                    descriptors.push(Array.from(descriptor));
                    await new Promise(r => setTimeout(r, 800));
                }

                updateStatus('جاري حفظ البيانات...', 'info');
                const res = await fetch("{{ route('face.register.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        center_code: centerCode,
                        descriptors: descriptors
                    })
                });

                const data = await res.json();
                if (data.success) {
                    savedCount++;
                    counterEl.innerText = savedCount;
                    updateStatus('تم الحفظ بنجاح', 'success');
                    alert('تم حفظ بصمة الوجه بنجاح');
                } else {
                    updateStatus('فشل الحفظ', 'danger');
                }
            } catch (err) {
                console.error(err);
                updateStatus('خطأ فني', 'danger');
            } finally {
                captureBtn.disabled = false;
                captureBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i> حفظ بصمة جديدة';
            }
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
@endsection
