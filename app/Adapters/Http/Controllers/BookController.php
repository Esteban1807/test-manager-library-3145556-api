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

// /books (api-contract §3.4)
final class BookController
{
    public function index(Request $request, SearchBooksService $search, CategoryReader $categories): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $size = (int) $request->query('size', 20);

        $result = $search->execute(
            $request->query('q'),
            $request->query('category_id'),
            $page,
            $size,
        );

        return response()->json([
            'items' => array_map(
                fn ($book) => Presenter::book($book, $categories->nameOf($book->categoryId()) ?? ''),
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
            'category_id' => ['required', 'string'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ]);

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
            'category_id' => ['required', 'string'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ]);

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

    private function findActive(BookRepository $books, string $id): \App\Domain\Book\Book
    {
        $book = $books->findById($id);

        if ($book === null || $book->isDeleted()) {
            throw new BookNotFound();
        }

        return $book;
    }
}
