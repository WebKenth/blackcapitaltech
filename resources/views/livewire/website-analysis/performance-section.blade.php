<div class="bg-slate-800/80 backdrop-blur-xl rounded-lg border border-slate-700/50 shadow-2xl">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-slate-700/50">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Performance & Lighthouse
            </h3>
            @if($website->lighthouse)
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
        @if($website->lighthouse)
            @php
                $metrics = [
                    ['key' => 'performance', 'label' => 'Performance', 'color' => 'emerald', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                    ['key' => 'accessibility', 'label' => 'Accessibility', 'color' => 'blue', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
                    ['key' => 'best-practices', 'label' => 'Best Practices', 'color' => 'purple', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                    ['key' => 'seo', 'label' => 'SEO Score', 'color' => 'orange', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                ];
            @endphp
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                @foreach($metrics as $metric)
                    @php
                        $score = $website->lighthouse[$metric['key']] ?? 0;
                        $scoreColor = $score >= 90 ? 'emerald' : ($score >= 70 ? 'amber' : 'red');
                    @endphp
                    <div class="relative bg-slate-900/50 backdrop-blur-sm rounded-lg p-4 border border-slate-700/50 group hover:border-{{ $metric['color'] }}-500/50 transition-all duration-300">
                        <div class="absolute inset-0 bg-gradient-to-br from-{{ $metric['color'] }}-500/5 to-transparent rounded-lg"></div>
                        <div class="relative">
                            <div class="flex items-center justify-between mb-2">
                                <svg class="w-5 h-5 text-{{ $metric['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $metric['icon'] }}"/>
                                </svg>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-{{ $scoreColor }}-400">{{ $score }}</div>
                                    <div class="w-12 h-1 bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-{{ $scoreColor }}-500 transition-all duration-500" style="width: {{ $score }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-400 uppercase tracking-wide">{{ $metric['label'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Detailed metrics if available -->
            @if(isset($website->lighthouse['metrics']))
                <div class="border-t border-slate-700/50 pt-6">
                    <h4 class="text-sm font-medium text-cyan-400 uppercase tracking-wide mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Core Web Vitals
                    </h4>
                    <div class="grid md:grid-cols-3 gap-4">
                        @if(isset($website->lighthouse['metrics']['first-contentful-paint']))
                            <div class="bg-slate-900/50 backdrop-blur-sm rounded-lg p-4 border border-slate-700/50">
                                <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">First Contentful Paint</div>
                                <div class="text-lg font-semibold text-white">{{ $website->lighthouse['metrics']['first-contentful-paint'] }}s</div>
                                <div class="text-xs text-emerald-400 mt-1">Good</div>
                            </div>
                        @endif
                        
                        @if(isset($website->lighthouse['metrics']['largest-contentful-paint']))
                            <div class="bg-slate-900/50 backdrop-blur-sm rounded-lg p-4 border border-slate-700/50">
                                <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Largest Contentful Paint</div>
                                <div class="text-lg font-semibold text-white">{{ $website->lighthouse['metrics']['largest-contentful-paint'] }}s</div>
                                <div class="text-xs text-amber-400 mt-1">Needs Improvement</div>
                            </div>
                        @endif
                        
                        @if(isset($website->lighthouse['metrics']['cumulative-layout-shift']))
                            <div class="bg-slate-900/50 backdrop-blur-sm rounded-lg p-4 border border-slate-700/50">
                                <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Cumulative Layout Shift</div>
                                <div class="text-lg font-semibold text-white">{{ $website->lighthouse['metrics']['cumulative-layout-shift'] }}</div>
                                <div class="text-xs text-emerald-400 mt-1">Good</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @else
            <!-- Loading state -->
            <div class="animate-pulse space-y-4">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="h-24 bg-slate-700 rounded-lg"></div>
                    <div class="h-24 bg-slate-700 rounded-lg"></div>
                    <div class="h-24 bg-slate-700 rounded-lg"></div>
                    <div class="h-24 bg-slate-700 rounded-lg"></div>
                </div>
                <div class="pt-6 border-t border-slate-700/50">
                    <div class="h-4 bg-slate-700 rounded w-32 mb-4"></div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="h-16 bg-slate-700 rounded-lg"></div>
                        <div class="h-16 bg-slate-700 rounded-lg"></div>
                        <div class="h-16 bg-slate-700 rounded-lg"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
