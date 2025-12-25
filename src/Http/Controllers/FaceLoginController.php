<?php

namespace ElDakhly\SmartFaceAuth\Http\Controllers;

use Illuminate\Routing\Controller;
use ElDakhly\SmartFaceAuth\Models\FaceDescriptor;
use ElDakhly\SmartFaceAuth\Models\FaceViolation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaceLoginController extends Controller
{
    public function showRegister()
    {
        return view('face-login::register');
    }

    public function showLogin()
    {
        return view('face-login::login');
    }

    /**
     * Store new face descriptors (multiple images)
     */
    public function store(Request $request)
    {
        $request->validate([
            'center_code' => 'required',
            'descriptors' => 'required|array',
            'descriptors.*' => 'required|array|size:128',
        ]);

        foreach ($request->descriptors as $descriptor) {
            FaceDescriptor::create([
                'center_code' => $request->center_code,
                'descriptor' => $descriptor,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Login using face recognition
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'descriptor' => 'required|array|size:128',
            ]);

            $faces = FaceDescriptor::all();

            foreach ($faces as $face) {
                $distance = $this->euclideanDistance(
                    $request->descriptor,
                    $face->descriptor
                );

                if ($distance < 0.6) {
                    $user = User::where('center_code', $face->center_code)->first();
                    if ($user) {
                        Auth::login($user);

                        // If LoginController exists, call authenticated method
                        if (class_exists(\App\Http\Controllers\Auth\LoginController::class)) {
                            $loginController = app(\App\Http\Controllers\Auth\LoginController::class);
                            if (method_exists($loginController, 'authenticated')) {
                                $response = $loginController->authenticated($request, $user);
                                if ($response instanceof \Illuminate\Http\RedirectResponse) {
                                    return response()->json([
                                        'success' => true,
                                        'redirect' => $response->getTargetUrl()
                                    ]);
                                }
                            }
                        }

                        return response()->json([
                            'success' => true,
                            'redirect' => config('fortify.home', '/home')
                        ]);
                    }
                }
            }

            return response()->json(['success' => false, 'message' => 'Face not recognized']);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function euclideanDistance($a, $b)
    {
        $sum = 0;
        foreach ($a as $i => $val) {
            $sum += pow($val - $b[$i], 2);
        }
        return sqrt($sum);
    }

    public function verifySession(Request $request)
    {
        $request->validate([
            'descriptor' => 'required|array|size:128',
        ]);

        $user = auth()->user();
        if (!$user) return response()->json(['valid' => false, 'message' => 'Unauthenticated'], 401);

        $faces = FaceDescriptor::where('center_code', $user->center_code)->get();

        foreach ($faces as $face) {
            $distance = $this->euclideanDistance(
                $request->descriptor,
                $face->descriptor
            );

            if ($distance < 0.6) {
                return response()->json(['valid' => true]);
            }
        }

        // Mismatch ➜ Logout
        FaceViolation::create([
            'user_id' => $user->id,
            'type' => 'MISMATCH',
        ]);

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return response()->json([
            'valid' => false,
            'logout' => true
        ], 401);
    }

    public function reportViolation(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:NO_FACE,MULTIPLE_FACES,SPOOF,PASS,MISMATCH',
        ]);

        FaceViolation::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
        ]);

        return response()->json(['success' => true]);
    }
}
