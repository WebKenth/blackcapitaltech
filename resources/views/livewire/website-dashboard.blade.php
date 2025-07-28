<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-800 relative overflow-hidden">
    <!-- Tech background pattern -->
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000">
            <defs>
                <pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse">
                    <path d="M 50 0 L 0 0 0 50" fill="none" stroke="currentColor" stroke-width="1"/>
                </pattern>
                <pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse">
                    <circle cx="10" cy="10" r="1" fill="currentColor"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" class="text-cyan-400"/>
            <rect width="100%" height="100%" fill="url(#dots)" class="text-blue-400"/>
        </svg>
    </div>
    
    <!-- Animated floating elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute top-3/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
        <div class="absolute top-1/2 left-1/2 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl animate-pulse delay-2000"></div>
    </div>

    <div class="relative z-10 max-w-6xl mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-8">
            <div class="bg-slate-800/80 backdrop-blur-xl rounded-lg border border-slate-700/50 p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-white">Website Analyse</h1>
                        <p class="text-sm text-slate-300 mt-1">{{ $website->url }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                            @if($website->is_processed) bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 @else bg-amber-500/20 text-amber-300 border border-amber-500/30 @endif">
                            @if($website->is_processed) 
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Færdig
                            @else 
                                <div class="w-3 h-3 mr-1 border border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                                Behandler
                            @endif
                        </span>
                        <span class="text-xs text-slate-400">{{ $website->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Sections -->
        <div class="space-y-6">
            <!-- Company Section -->
            <livewire:website-analysis.company-section :website="$website" />
            
            <!-- Sitemap Section -->
            <livewire:sitemap-section :website="$website" />
            
            <!-- SEO Section -->
            <livewire:website-analysis.seo-section :website="$website" />
            
            <!-- Performance Section -->
            <livewire:website-analysis.performance-section :website="$website" />
        </div>
    </div>
</div>