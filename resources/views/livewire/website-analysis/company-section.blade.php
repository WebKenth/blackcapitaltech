<div class="bg-slate-800/80 backdrop-blur-xl rounded-lg border border-slate-700/50 shadow-2xl">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-slate-700/50">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Virksomhed & Strategi
            </h3>
            @if($website->company || $website->goal)
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Færdig
                </span>
            @else
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    <div class="w-3 h-3 mr-1 border border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                    Behandler
                </span>
            @endif
        </div>
    </div>

    <!-- Content -->
    <div class="p-6">
        @if($website->company || $website->goal)
            <div class="grid lg:grid-cols-2 gap-6">
                <!-- Company Information -->
                @if($website->company)
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-cyan-400 uppercase tracking-wide">Virksomhedsdata</h4>
                        
                        <div class="grid grid-cols-1 gap-3">
                            @if(isset($website->company['name']))
                                <div class="flex justify-between py-2 border-b border-slate-700/50">
                                    <span class="text-sm text-slate-400">Navn</span>
                                    <span class="text-sm font-medium text-white">{{ $website->company['name'] }}</span>
                                </div>
                            @endif
                            
                            @if(isset($website->company['cvr']))
                                <div class="flex justify-between py-2 border-b border-slate-700/50">
                                    <span class="text-sm text-slate-400">CVR</span>
                                    <span class="text-sm font-medium text-cyan-300 font-mono">{{ $website->company['cvr'] }}</span>
                                </div>
                            @endif
                            
                            @if(isset($website->company['industry']))
                                <div class="flex justify-between py-2 border-b border-slate-700/50">
                                    <span class="text-sm text-slate-400">Branche</span>
                                    <span class="text-sm font-medium text-white">{{ $website->company['industry'] }}</span>
                                </div>
                            @endif
                            
                            @if(isset($website->company['size']))
                                <div class="flex justify-between py-2 border-b border-slate-700/50">
                                    <span class="text-sm text-slate-400">Størrelse</span>
                                    <span class="text-sm font-medium text-white">{{ $website->company['size'] }}</span>
                                </div>
                            @endif
                            
                            @if(isset($website->company['location']))
                                <div class="flex justify-between py-2">
                                    <span class="text-sm text-slate-400">Lokation</span>
                                    <span class="text-sm font-medium text-white">{{ $website->company['location'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Goals & Strategy -->
                @if($website->goal)
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-purple-400 uppercase tracking-wide">Mål & Strategi</h4>
                        <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 backdrop-blur-sm">
                            <p class="text-sm text-blue-200 leading-relaxed">{{ $website->goal }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <!-- Loading state -->
            <div class="animate-pulse space-y-4">
                <div class="grid lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="h-4 bg-slate-700 rounded w-32"></div>
                        <div class="h-3 bg-slate-700 rounded w-full"></div>
                        <div class="h-3 bg-slate-700 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-700 rounded w-5/6"></div>
                    </div>
                    <div class="space-y-3">
                        <div class="h-4 bg-slate-700 rounded w-28"></div>
                        <div class="h-16 bg-slate-700 rounded w-full"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
