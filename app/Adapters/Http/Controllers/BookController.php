<?php

namespace App\Adapters\Http\Controllers;

use App\Adapters\Http\Presenter;
use App\Application\Errors\BookNotFound;
use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryReader;
use App\Application\Ports\CoverStorage;
use App\Application\Ports\IdGenerator;
use App\Application\UseCases\CreateBookService;
use App\Application\UseCases\DeleteBookService;
use App\Application\UseCases\RemoveCoverService;
use App\Application\UseCases\SearchBooksService;
use App\Application\UseCases\UpdateBookService;
use App\Application\UseCases\UploadCoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

// /books (api-contract §3.4)
final class BookController
{
    public function index(Request $request, SearchBooksService $search, CategoryReader $categories): JsonResponse
    {
        $request->validate(['category_id' => ['nullable', 'uuid']]);

        $page = (int) $request->query('page', 1);
        $size = (int) $request->query('size', 20);

        $result = $search->execute(
            $request->query('q'),
            $request->query('category_id'),
            $page,
            $size,
        );

        // One query for the five category names, instead of one per book in the page
        $names = array_column($categories->all(), 'name', 'id');

        return response()->json([
            'items' => array_map(
                fn ($book) => Presenter::book($book, $names[$book->categoryId()] ?? ''),
                $result->items,
            ),
            'page' => $result->page,
            'size' => $result->size,
            'total' => $result->total,
        ]);
    }

    public function show(string $id, BookRepository $books, CategoryReader $categories): JsonResponse
    {
        $book = $this->findActive($books, $id);

        return response()->json(Presenter::book($book, $categories->nameOf($book->categoryId()) ?? ''));
    }

    public function store(Request $request, CreateBookService $create, CategoryReader $categories, IdGenerator $ids): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:250'],
            'author' => ['required', 'string', 'max:200'],
            'isbn' => ['required', 'string'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ]);

        $this->assertCategoryExists($categories, $data['category_id']);

        $book = $create->execute(
            $ids->next(),
            $data['title'],
            $data['author'],
            $data['isbn'],
            $data['category_id'],
            (int) $data['total_copies'],
        );

        return response()->json(Presenter::book($book, $categories->nameOf($book->categoryId()) ?? ''), 201);
    }

    public function update(Request $request, string $id, UpdateBookService $update, CategoryReader $categories): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:250'],
            'author' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ]);

        $this->assertCategoryExists($categories, $data['category_id']);

        $book = $update->execute($id, $data['title'], $data['author'], $data['category_id'], (int) $data['total_copies']);

        return response()->json(Presenter::book($book, $categories->nameOf($book->categoryId()) ?? ''));
    }

    public function destroy(string $id, DeleteBookService $delete): Response
    {
        $delete->execute($id);

        return response()->noContent();
    }

    public function uploadCover(Request $request, string $id, UploadCoverService $upload, CategoryReader $categories): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimetypes:image/jpeg,image/png'],
        ]);

        $file = $request->file('file');
        $book = $upload->execute($id, file_get_contents($file->getRealPath()), $file->getMimeType());

        return response()->json(Presenter::book($book, $categories->nameOf($book->categoryId()) ?? ''));
    }

    public function showCover(string $id, BookRepository $books, CoverStorage $storage): Response
    {
        $book = $this->findActive($books, $id);
        $key = $book->coverKey();

        if ($key === null) {
            throw new BookNotFound();
        }

        return response($storage->read($key), 200, [
            'Content-Type' => str_ends_with($key, '.png') ? 'image/png' : 'image/jpeg',
        ]);
    }

    public function removeCover(string $id, RemoveCoverService $remove, CategoryReader $categories): JsonResponse
    {
        $book = $remove->execute($id);

        return response()->json(Presenter::book($book, $categories->nameOf($book->categoryId()) ?? ''));
    }

    // A category that is not one of the five fixed ones is an input error (422), not a server error
    private function assertCategoryExists(CategoryReader $categories, string $categoryId): void
    {
        if ($categories->nameOf($categoryId) === null) {
            throw ValidationException::withMessages(['category_id' => 'La categoría no existe.']);
        }
    }

    private function findActive(BookRepository $books, string $id): \App\Domain\Book\Book
    {
        $book = $books->findById($id);

        if ($book === null || $book->isDeleted()) {
            throw new BookNotFound();
        }

        return $book;
    }
}
