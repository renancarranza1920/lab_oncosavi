@extends('errors::minimal')

@section('title', __('Prohibido'))
@section('code', '403')
@section('message')
    <div style="text-align: center; font-family: sans-serif;">
        <h1 style="font-size: 24px; color: #333;">Acceso Denegado</h1>
        <p style="font-size: 16px; color: #666;">
            No tienes los permisos necesarios para acceder a esta página.
        </p>
        <a href="{{ app('router')->has('filament.admin.pages.dashboard') ? route('filament.admin.pages.dashboard') : url('/') }}"
           style="display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #090B3B; color: white; text-decoration: none; border-radius: 5px;"
        >
            Volver al Inicio
        </a>
    </div>
@endsection