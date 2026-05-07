<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GroupController extends Controller
{
    private function flushMasters(): void
    {
        Cache::forget('barang.masters');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));

        $query = Group::query()
            ->select(['id', 'kode_group', 'nama_group'])
            ->withCount('barangs');

        if ($search !== '') {
            $like = $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nama_group', 'like', $like)
                  ->orWhere('kode_group', 'like', $like);
            });
        }

        $groups = $query->latest('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Group/Index', [
            'groups'  => $groups,
            'filters' => ['search' => $search, 'perPage' => $perPage],
        ]);
    }

    public function store(GroupRequest $request)
    {
        Group::create($request->validated());
        $this->flushMasters();
        return back()->with('success', 'Group berhasil ditambah');
    }

    public function update(GroupRequest $request, Group $group)
    {
        $group->update($request->validated());
        $this->flushMasters();
        return back()->with('success', 'Group berhasil diubah');
    }

    public function destroy(Group $group)
    {
        if ($group->barangs()->exists()) {
            return back()->with('error', "Group '{$group->nama_group}' masih digunakan barang.");
        }
        $group->delete();
        $this->flushMasters();
        return back()->with('success', 'Group berhasil dihapus');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:groups,id'],
        ]);

        $rows = Group::whereIn('id', $data['ids'])->withCount('barangs')->get();
        $deletable = $rows->where('barangs_count', 0);
        $blockedCount = $rows->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return back()->with('error', 'Semua group yang dipilih masih digunakan barang.');
        }

        $deleted = Group::whereIn('id', $deletable->pluck('id'))->delete();
        $this->flushMasters();
        $msg = "{$deleted} group dihapus"
            . ($blockedCount ? ", {$blockedCount} dilewati (masih digunakan)" : '');
        return back()->with('success', $msg);
    }
}
