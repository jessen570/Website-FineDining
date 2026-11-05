<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Akun — Maison Aster ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: '#18382E',
                        emerald: '#244A3D',
                        gold: '#C6A76B',
                        'gold-muted': '#A88C58',
                        ivory: '#F7F5F0',
                        cream: '#EEE9DE',
                        charcoal: '#292B28',
                    },
                    fontFamily: {
                        serif: ['"Cormorant Garamond"', '"Playfair Display"', 'Georgia', 'serif'],
                        display: ['"Playfair Display"', 'Georgia', 'serif'],
                        sans: ['"Inter"', 'system-ui', '-apple-system', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F7F5F0;
            color: #292B28;
            -webkit-font-smoothing: antialiased;
        }

        /* Ambient restaurant backdrop with deep emerald atmosphere */
        .brand-backdrop {
            background-image: 
                linear-gradient(140deg, rgba(24, 56, 46, 0.90) 0%, rgba(36, 74, 61, 0.78) 45%, rgba(15, 34, 27, 0.94) 100%),
                url('https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }

        /* Custom focus transitions for inputs */
        .luxury-input {
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }
        .luxury-input:focus {
            border-color: #18382E;
            box-shadow: 0 0 0 3px rgba(24, 56, 46, 0.12), 0 1px 2px rgba(0,0,0,0.05);
            background-color: #FFFFFF;
        }

        /* Custom button styling */
        .btn-luxury {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-luxury:hover {
            background-color: #244A3D;
            border-color: rgba(198, 167, 107, 0.5);
            box-shadow: 0 6px 20px -4px rgba(24, 56, 46, 0.35);
        }
        .btn-luxury:active {
            transform: scale(0.99);
        }

        /* Custom checkbox */
        .luxury-checkbox:checked {
            background-color: #18382E;
            border-color: #18382E;
        }
    </style>
</head>
<body class="h-full overflow-x-hidden">

    <div class="min-h-screen flex flex-col lg:flex-row">

        {{-- ======================================================== --}}
        {{-- LEFT SIDE: BRAND IDENTITY (~52% DESKTOP)                 --}}
        {{-- ======================================================== --}}
        <section class="brand-backdrop relative w-full lg:w-[52%] min-h-[280px] lg:min-h-screen flex items-center justify-center p-8 lg:p-16 select-none overflow-hidden">
            
            {{-- Subtle Vignette & Depth Overlay --}}
            <div class="absolute inset-0 bg-radial from-transparent via-[#18382E]/30 to-[#0F221B]/80 pointer-events-none"></div>

            {{-- Refined Center Brand Plaque --}}
            <div class="relative z-10 text-center max-w-md mx-auto px-6 py-8">
                
                {{-- Refined Aster Crest Monogram --}}
                <div class="mb-6 flex justify-center">
                    <img src="{{ asset('images/logo-icon.jpg') }}" alt="Maison Aster" class="w-16 h-16 rounded-2xl object-cover border border-[#C6A76B]/50 shadow-xl p-0.5 bg-[#18382E]">
                </div>

                {{-- Brand Title --}}
                <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl tracking-[0.22em] uppercase text-white font-normal leading-tight">
                    MAISON ASTER
                </h1>

                {{-- Delicate Gold Accent Line --}}
                <div class="w-16 h-[1px] bg-gradient-to-r from-transparent via-[#C6A76B] to-transparent mx-auto my-4 opacity-75"></div>

                {{-- Subtitle --}}
                <p class="font-sans text-[11px] sm:text-xs tracking-[0.38em] uppercase text-[#C6A76B] font-medium">
                    FINE DINING ERP
                </p>

            </div>

            {{-- Subtle bottom corner coordinate decoration --}}
            <div class="hidden lg:block absolute bottom-6 left-8 text-[10px] tracking-[0.25em] uppercase text-[#C6A76B]/40 font-light font-mono">
                PRIVATE ACCESS PORTAL
            </div>

        </section>

        {{-- ======================================================== --}}
        {{-- RIGHT SIDE: LOGIN FORM (~48% DESKTOP)                    --}}
        {{-- ======================================================== --}}
        <section class="w-full lg:w-[48%] flex-1 bg-ivory flex items-center justify-center p-6 sm:p-10 lg:p-16">
            
            <div class="w-full max-w-[420px] mx-auto">
                
                {{-- Form Header --}}
                <div class="mb-8">
                    <h2 class="font-serif text-2xl sm:text-3xl text-charcoal font-semibold tracking-tight">
                        Masuk ke Akun
                    </h2>
                    <div class="w-8 h-[2px] bg-gold mt-2.5"></div>
                </div>

                {{-- Validation Errors --}}
                @if(isset($errors) && $errors->any())
                    <div class="mb-6 p-4 rounded-lg bg-red-50/80 border border-red-200 text-sm text-red-800 flex items-start space-x-3 transition-all" role="alert">
                        <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="leading-snug">{{ $errors->first() }}</span>
                    </div>
                @endif

                {{-- Authentication Form --}}
                <form method="POST" action="{{ route('login.post') }}" class="space-y-5" autocomplete="on">
                    @csrf

                    {{-- Email Field --}}
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-charcoal/80 mb-2">
                            Email
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="nama@maisonaster.id"
                                required
                                autofocus
                                class="luxury-input w-full px-4 py-3 bg-white border border-[#E2DCD2] rounded-md text-sm text-charcoal placeholder-[#9E9A90] focus:outline-none @if(isset($errors) && $errors->has('email')) border-red-400 @endif"
                            >
                        </div>
                    </div>

                    {{-- Password Field with Visibility Toggle --}}
                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-charcoal/80 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                                class="luxury-input w-full pl-4 pr-11 py-3 bg-white border border-[#E2DCD2] rounded-md text-sm text-charcoal placeholder-[#9E9A90] focus:outline-none"
                            >
                            <button
                                type="button"
                                id="password-toggle"
                                onclick="togglePasswordVisibility()"
                                aria-label="Toggle password visibility"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#8C8880] hover:text-forest transition-colors focus:outline-none"
                            >
                                {{-- Eye Open Icon --}}
                                <svg id="eye-open" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                {{-- Eye Closed Icon (hidden initially) --}}
                                <svg id="eye-closed" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                    <line x1="2" y1="2" x2="22" y2="22" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Additional Controls: Remember Me & Forgot Password --}}
                    <div class="flex items-center justify-between pt-1 text-sm">
                        <label for="remember" class="inline-flex items-center cursor-pointer select-none">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="luxury-checkbox w-4 h-4 rounded border-[#D5CEC2] text-forest focus:ring-forest focus:ring-offset-0 transition"
                            >
                            <span class="ml-2 text-xs sm:text-sm text-charcoal/80">Ingat saya</span>
                        </label>

                        <a href="javascript:void(0)" onclick="alert('Silakan hubungi Administrator IT Maison Aster untuk reset kredensial akun.')" class="text-xs sm:text-sm text-gold-muted hover:text-forest transition-colors font-medium">
                            Lupa password?
                        </a>
                    </div>

                    {{-- Primary Button --}}
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="btn-luxury w-full bg-forest text-white py-3.5 px-6 rounded-md text-sm font-medium tracking-wide shadow-sm border border-gold/30 cursor-pointer"
                        >
                            Masuk
                        </button>
                    </div>

                </form>

            </div>

        </section>

    </div>

    {{-- Script for password visibility toggle --}}
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }
    </script>

</body>
</html>
