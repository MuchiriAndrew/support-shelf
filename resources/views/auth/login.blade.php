@extends('layouts.app', ['pageTitle' => 'Login', 'contentWidth' => 'max-w-3xl'])

@section('content')
    @php
        $cardClass = 'mx-auto max-w-xl rounded-[2rem] border border-[color:var(--border-soft)] bg-[var(--surface-elevated)] p-8 shadow-[0_24px_80px_rgba(0,0,0,0.18)] sm:p-10';
        $inputClass = 'w-full rounded-2xl border border-[color:var(--border-soft)] bg-[var(--surface-base)] px-4 py-3 text-base text-[var(--text-primary)] outline-none transition focus:border-[var(--brand-primary)]';
        $buttonClass = 'inline-flex items-center gap-2 rounded-full bg-[var(--button-primary-bg)] px-6 py-3 text-sm font-semibold text-[var(--button-primary-text)] shadow-[var(--shadow-card)] transition hover:-translate-y-0.5 hover:opacity-95';
    @endphp
    <section class="py-16 sm:py-24">
        <div class="{{ $cardClass }}">
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-[var(--text-muted)]">Welcome back</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em] text-[var(--text-primary)]">Log in to your private assistant workspace</h1>
            <p class="mt-4 text-base leading-7 text-[var(--text-secondary)]">
                Sign in to chat with your assistant, manage knowledge sources, and keep your context private to your account.
            </p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-10 space-y-6">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-[var(--text-primary)]">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="{{ $inputClass }}" />
                    @error('email')
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-[var(--text-primary)]">Password</label>
                    <input id="password" name="password" type="password" required class="{{ $inputClass }}" />
                    @error('password')
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 text-sm text-[var(--text-secondary)]">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-[color:var(--border-soft)] bg-transparent text-[var(--brand-primary)]" />
                    <span>Keep me signed in</span>
                </label>

                <button type="submit" class="{{ $buttonClass }} w-full justify-center">
                    Log in
                </button>
            </form>

            <p class="mt-8 text-sm text-[var(--text-secondary)]">
                Need an account?
                <a href="{{ route('register') }}" class="font-medium text-[var(--brand-primary)]">Create one here</a>
            </p>
        </div>
    </section>
@endsection
