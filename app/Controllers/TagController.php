<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Tag;

final class TagController extends Controller
{
    public function index(Request $request): void
    {
        $tags = new Tag();
        Response::view('categories/tags', [
            'title' => 'Tags',
            'tags' => $tags->listForTeam($this->teamId()),
            'role' => $this->role(),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->can('tag.manage')) {
            Response::abort(403);
        }
        $name = Tag::normalize((string) $request->input('name', ''));
        if ($name === '') {
            $this->respond($request, false, 'Tag name is required.', null, '/tags');
            return;
        }
        $tags = new Tag();
        if ($tags->findByName($this->teamId(), $name) !== null) {
            $this->respond($request, false, 'This tag already exists.', null, '/tags');
            return;
        }
        $id = $tags->insert(['team_id' => $this->teamId(), 'name' => $name, 'created_at' => date('Y-m-d H:i:s')]);
        $this->respond($request, true, 'Tag created.', ['id' => $id, 'name' => $name], '/tags');
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->can('tag.manage')) {
            Response::abort(403);
        }
        $tags = new Tag();
        if (!$tags->belongsToTeam((int) $id, $this->teamId())) {
            Response::abort(404);
        }
        $name = Tag::normalize((string) $request->input('name', ''));
        $tags->update((int) $id, ['name' => $name]);
        $this->respond($request, true, 'Tag updated.', null, '/tags');
    }

    public function delete(Request $request, string $id): void
    {
        if (!$this->can('tag.manage')) {
            Response::abort(403);
        }
        $tags = new Tag();
        if (!$tags->belongsToTeam((int) $id, $this->teamId())) {
            Response::abort(404);
        }
        if ($tags->isInUse((int) $id)) {
            $this->respond($request, false, 'This tag is used by existing expenses and cannot be deleted.', null, '/tags');
            return;
        }
        $tags->execute('DELETE FROM tags WHERE id = :id', ['id' => $id]);
        $this->respond($request, true, 'Tag deleted.', null, '/tags');
    }
}
