<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\ConvertJobApplicationRequest;
use App\Http\Requests\Recruitment\UpdateJobApplicationStatusRequest;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * JobApplicationController (Recruitment)
 * ---------------------------------------------------------------------
 * Fase 3 — panel pelamar untuk HRD/Owner: lihat semua lamaran masuk,
 * geser status pipeline (baru -> ditinjau -> interview -> ditawari ->
 * diterima/ditolak), simpan catatan internal, dan convert pelamar yang
 * diterima jadi akun karyawan beneran lewat form `convert`/`storeConvert`
 * (isinya sama seperti Owner\EmployeeController@store, tapi nama & email
 * sudah keisi duluan dari data lamaran).
 * ---------------------------------------------------------------------
 */
class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = JobApplication::query()->with('jobOpening')->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($openingId = $request->integer('lowongan')) {
            $query->where('job_opening_id', $openingId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate(15)->withQueryString();

        return view('recruitment.applications.index', [
            'applications' => $applications,
            'filters' => $request->only(['status', 'lowongan', 'q']),
            'openings' => JobOpening::query()->orderBy('title')->get(['id', 'title']),
            'statuses' => JobApplication::STATUSES,
        ]);
    }

    public function show(JobApplication $application)
    {
        $application->load('jobOpening', 'convertedUser');

        return view('recruitment.applications.show', [
            'application' => $application,
            'statuses' => JobApplication::STATUSES,
        ]);
    }

    public function updateStatus(UpdateJobApplicationStatusRequest $request, JobApplication $application)
    {
        $application->update($request->validated());

        return back()->with('status', 'Status pelamar berhasil diperbarui.');
    }

    public function convert(JobApplication $application)
    {
        if ($application->isConverted()) {
            return redirect()->route('recruitment.applications.show', $application)
                ->with('error', 'Pelamar ini sudah pernah di-convert jadi karyawan.');
        }

        $managers = User::query()->orderBy('name')->get();

        return view('recruitment.applications.convert', ['application' => $application, 'managers' => $managers]);
    }

    public function storeConvert(ConvertJobApplicationRequest $request, JobApplication $application)
    {
        if ($application->isConverted()) {
            return redirect()->route('recruitment.applications.show', $application)
                ->with('error', 'Pelamar ini sudah pernah di-convert jadi karyawan.');
        }

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        $application->update([
            'converted_user_id' => $user->id,
            'status' => 'diterima',
        ]);

        return redirect()->route('recruitment.applications.show', $application)
            ->with('status', "{$user->name} berhasil dibuatkan akun karyawan.");
    }
}