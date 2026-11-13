@extends('layouts.app')

@section('title', 'Halaman tidak ditemukan')

@section('content')
<div class="container py-5 text-center" style="min-height:52vh">
    <div class="card-eyebrow">ERROR 404</div>
    <h1 class="fw-bold" style="font-size:clamp(4rem,16vw,9rem);line-height:.95">404</h1>
    <p class="text-secondary mb-4">Halaman yang kamu cari tidak ada, sudah dipindahkan, atau tautannya salah.</p>
    <div class="d-flex justify-content-center gap-2 flex-wrap">
        <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Home</a>
        <a href="{{ route('search', ['type' => 'anime']) }}" class="btn btn-outline-light">Jelajahi katalog</a>
    </div>
</div>
@endsection
