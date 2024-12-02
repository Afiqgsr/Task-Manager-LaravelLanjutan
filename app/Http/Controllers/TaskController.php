<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    // Menampilkan semua task
    public function index()
    {
        // Mengambil semua task dari database (tidak ada soft delete)
        $tasks = Task::all();
        return view('tasks.index', compact('tasks'));
    }

    // Menampilkan form untuk membuat task baru
    public function create()
    {
        // Ambil semua kategori untuk dropdown di form
        $categories = Category::all();
        return view('tasks.create', compact('categories'));
    }

    // Menyimpan task baru
    public function store(Request $request)
    {
        // Validasi data
        $validatedData = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'required|date',
            'category_id' => 'required|exists:categories,id', // Validasi kategori
        ]);

        // Tambahkan user_id dari pengguna yang sedang login
        $validatedData['user_id'] = Auth::id();

        // Simpan data ke database
        Task::create($validatedData);

        return redirect()->route('tasks.index')->with('success', 'Task berhasil ditambahkan');
    }

    // Menampilkan detail task
    public function show(Task $task)
    {
        // Memuat task beserta histori status (assume 'histories' relationship exists)
        $task->load('histories');

        return view('tasks.show', compact('task'));
    }

    // Menampilkan form untuk mengedit task
    public function edit(Task $task)
    {
        // Pastikan pengguna yang sedang login adalah pemilik task
        if (Auth::id() !== $task->user_id) {
            return redirect()->route('tasks.index')->with('error', 'Anda tidak memiliki akses untuk mengedit task ini.');
        }

        // Ambil semua kategori untuk dropdown di form
        $categories = Category::all();

        return view('tasks.edit', compact('task', 'categories'));
    }

    // Mengupdate task
    public function update(Request $request, Task $task)
    {
        // Pastikan pengguna yang sedang login adalah pemilik task
        if (Auth::id() !== $task->user_id) {
            return redirect()->route('tasks.index')->with('error', 'Anda tidak memiliki akses untuk mengupdate task ini.');
        }

        // Validasi data
        $validatedData = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'required|date',
            'category_id' => 'required|exists:categories,id', // Validasi kategori
        ]);

        // Cek apakah status berubah dan simpan histori perubahan
        if ($task->status !== $validatedData['status']) {
            $task->changeStatus($validatedData['status'], 'Status updated via task update');
        }

        // Update task
        $task->update($validatedData);

        return redirect()->route('tasks.index')->with('success', 'Task berhasil diperbarui.');
    }

    // Menghapus task
    public function destroy(Task $task)
    {
        // Pastikan pengguna yang sedang login adalah pemilik task
        if (Auth::id() !== $task->user_id) {
            return redirect()->route('tasks.index')->with('error', 'Anda tidak memiliki akses untuk menghapus task ini.');
        }

        // Menghapus task secara permanen
        $task->forceDelete();

        return redirect()->route('tasks.index')->with('success', 'Task berhasil dihapus secara permanen.');
    }

    // Menampilkan task berdasarkan kategori
    public function indexByCategory(Category $category)
    {
        // Ambil task berdasarkan kategori
        $tasks = $category->tasks; // Misalkan relasi 'tasks' sudah didefinisikan di model Category

        return view('tasks.index', compact('tasks', 'category'));
    }
}
