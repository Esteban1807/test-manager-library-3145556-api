<?php

namespace App\Adapters\Http\Controllers;

use App\Adapters\Http\Presenter;
use App\Application\Errors\MemberNotFound;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Application\UseCases\CreateMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// /members (api-contract §3.5). Admin and librarian only (enforced by routes).
final class MemberController
{
    public function index(Request $request, MemberRepository $members): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $size = min(50, max(1, (int) $request->query('size', 20)));

        $result = $members->search($request->query('q'), $page, $size);

        return response()->json([
            'items' => array_map(Presenter::member(...), $result->items),
            'page' => $result->page,
            'size' => $result->size,
            'total' => $result->total,
        ]);
    }

    public function show(string $id, MemberRepository $members, LoanRepository $loans): JsonResponse
    {
        $member = $members->findById($id);

        if ($member === null) {
            throw new MemberNotFound();
        }

        return response()->json(Presenter::member($member) + [
            'active_loans' => $loans->countOpenItemsForMember($id),
        ]);
    }

    public function store(Request $request, CreateMemberService $create, IdGenerator $ids): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'document_number' => ['required', 'string', 'max:30'],
        ]);

        $member = $create->execute($ids->next(), $data['full_name'], $data['document_number']);

        return response()->json(Presenter::member($member), 201);
    }
}
