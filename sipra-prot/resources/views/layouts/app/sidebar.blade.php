<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-sipra-fundo dark:bg-zinc-800">
        {{-- A classe "dark" aqui faz o menu usar textos claros sobre o fundo azul --}}
        <flux:sidebar sticky collapsible="mobile" class="dark border-e border-sipra-azul bg-sipra-azul lg:h-dvh">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 px-2 py-1">
                    <img src="{{ asset('img/sipra-simbolo-branco.png') }}" alt="SIPRA" class="h-10 w-auto">
                    <span class="font-titulo text-xl font-bold tracking-wide text-white">SIPRA</span>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Menu" class="grid">
                    <flux:sidebar.item icon="folder" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate class="data-current:bg-sipra-verde! data-current:text-white!">
                        Meus Projetos
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="users" :href="route('clientes.index')" :current="request()->routeIs('clientes.*')" wire:navigate class="data-current:bg-sipra-verde! data-current:text-white!">
                        Clientes
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="document-text" href="#">
                        Propostas
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="chart-bar" href="#">
                        Relatórios
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Cabeçalho no celular -->
        <flux:header class="dark border-b border-white/10 bg-sipra-azul lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            Configurações
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            Sair