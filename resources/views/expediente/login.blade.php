@extends('expediente.layout')
@section('title', 'Acceso de médicos')
@section('body-class', 'portal-login-body')
@section('content')
    <section class="portal-login-card portal-card">
        <div class="portal-login-symbol"><x-heroicon-o-folder-open class="portal-icon" /></div>
        <p class="portal-eyebrow">Portal de médicos</p>
        <h1>Consulta de expedientes</h1>
        <p class="portal-muted">Accede a los antecedentes de laboratorio y resultados de tus pacientes.</p>
        <form method="post" action="{{ route('expediente.login.store') }}" class="portal-login-form">
            @csrf
            <label class="portal-field">Usuario
                <input name="usuario" value="{{ old('usuario', 'medicos') }}" placeholder="medicos" autocomplete="username" required maxlength="32" autofocus>
            </label>
            <label class="portal-field">Contraseña
                <span class="portal-password-field">
                    <input id="portal-password" name="password" type="password" autocomplete="current-password" required maxlength="128">
                    <button type="button" class="portal-password-toggle" data-password-toggle="portal-password" aria-label="Mostrar contraseña" aria-pressed="false">
                        <x-heroicon-o-eye class="portal-icon portal-password-show" />
                        <x-heroicon-o-eye-slash class="portal-icon portal-password-hide" hidden />
                    </button>
                </span>
            </label>
            <button class="portal-button portal-button-primary" type="submit">Entrar al expediente<x-heroicon-o-arrow-right class="portal-icon" /></button>
        </form>
        <p class="portal-login-help">Solicita tu usuario y contraseña al laboratorio.</p>
        <a class="portal-contact" href="{{ config('laboratorio.telefono_uri') }}"><x-heroicon-o-phone class="portal-icon" />{{ config('laboratorio.telefono') }}</a>
    </section>
@endsection
