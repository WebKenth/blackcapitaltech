<div class="bg-slate-800/80 backdrop-blur-xl rounded-lg border border-slate-700/50 shadow-2xl">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-slate-700/50">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                SEO Analyse
            </h3>
            @if($website->seo)
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
        @if($website->seo)
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Meta Information -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-cyan-400 uppercase tracking-wide">Meta Tags</h4>
                    
                    @if(isset($website->seo['title']))
                        <div class="border border-slate-700/50 bg-slate-900/50 rounded-lg p-4 backdrop-blur-sm">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs text-slate-400 uppercase tracking-wide">Title</span>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    {{ strlen($website->seo['title']) }} tegn
                                </span>
                            </div>
                            <p class="text-sm text-white">{{ $website->seo['title'] }}</p>
                        </div>
                    @endif
                    
                    @if(isset($website->seo['description']))
                        <div class="border border-slate-700/50 bg-slate-900/50 rounded-lg p-4 backdrop-blur-sm">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs text-slate-400 uppercase tracking-wide">Description</span>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                    {{ strlen($website->seo['description']) }} tegn
                                </span>
                            </div>
                            <p class="text-sm text-slate-200">{{ $website->seo['description'] }}</p>
                        </div>
                    @endif
                </div>

                <!-- Technical SEO -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-purple-400 uppercase tracking-wide">Teknisk SEO</h4>
                    
                    <div class="space-y-3">
                        @php
                            $seoChecks = [
                                ['key' => 'hasTitle', 'label' => 'Meta Title', 'check' => isset($website->seo['title']) && !empty($website->seo['title'])],
                                ['key' => 'hasDescription', 'label' => 'Meta Description', 'check' => isset($website->seo['description']) && !empty($website->seo['description'])],
                                ['key' => 'hasHeadings', 'label' => 'Heading Structure', 'check' => isset($website->seo['headings']) && count($website->seo['headings']) > 0],
                                ['key' => 'hasImages', 'label' => 'Image Alt Tags', 'check' => isset($website->seo['images_with_alt']) && $website->seo['images_with_alt'] > 0],
                            ];
                        @endphp
                        
                        @foreach($seoChecks as $check)
                            <div class="flex items-center justify-between py-2 border-b border-slate-700/50">
                                <span class="text-sm text-slate-300">{{ $check['label'] }}</span>
                                <div class="flex items-center">
                                    @if($check['check'])
                                        <div class="w-6 h-6 bg-emerald-500/20 rounded-full flex items-center justify-center border border-emerald-500/30">
                                            <svg class="w-3 h-3 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-6 h-6 bg-red-500/20 rounded-full flex items-center justify-center border border-red-500/30">
                                            <svg class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <!-- Loading state -->
            <div class="animate-pulse space-y-4">
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="h-4 bg-slate-700 rounded w-24"></div>
                        <div class="h-20 bg-slate-700 rounded w-full"></div>
                        <div class="h-20 bg-slate-700 rounded w-full"></div>
                    </div>
                    <div class="space-y-3">
                        <div class="h-4 bg-slate-700 rounded w-28"></div>
                        <div class="h-3 bg-slate-700 rounded w-full"></div>
                        <div class="h-3 bg-slate-700 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-700 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
