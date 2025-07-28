<div class="w-full max-w-md mx-auto" x-data="urlAnalyzer()">
    <form @submit.prevent="handleSubmit()" class="space-y-4">
        <div>
            <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                Website URL
            </label>
            <input 
                type="text" 
                id="url"
                wire:model="url" 
                x-model="inputUrl"
                placeholder="example.com"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :disabled="isLoading"
            >
            @error('url') 
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p> 
            @enderror
            <p x-show="error" x-text="error" class="text-red-500 text-sm mt-1"></p>
        </div>
        
        <button 
            type="submit" 
            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
            :disabled="isLoading"
        >
            <svg x-show="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isLoading ? 'Analyserer...' : 'Analyser website'"></span>
        </button>
    </form>
    
    <script>
        function urlAnalyzer() {
            return {
                inputUrl: @entangle('url'),
                isLoading: @entangle('isLoading'),
                error: '',
                
                normalizeUrl(input) {
                    input = input.trim();
                    
                    // If it already has a scheme, return as is
                    if (/^https?:\/\//.test(input)) {
                        return input;
                    }
                    
                    // Remove any protocol if present
                    input = input.replace(/^(https?:\/\/)/, '');
                    
                    // Basic domain validation - must contain at least one dot
                    if (!input.includes('.')) {
                        return null;
                    }
                    
                    // Add https:// prefix
                    return 'https://' + input;
                },
                
                async handleSubmit() {
                    this.error = '';
                    
                    if (!this.inputUrl || this.inputUrl.length < 3) {
                        this.error = 'Indtast venligst en URL';
                        return;
                    }
                    
                    const normalizedUrl = this.normalizeUrl(this.inputUrl);
                    
                    if (!normalizedUrl) {
                        this.error = 'Indtast venligst en gyldig URL (f.eks. example.com)';
                        return;
                    }
                    
                    this.isLoading = true;
                    
                    try {
                        // Try to fetch the URL to validate it exists
                        const response = await fetch(normalizedUrl, {
                            method: 'HEAD',
                            mode: 'no-cors'
                        });
                        
                        // If we get here, the URL is accessible
                        @this.call('createWebsite', normalizedUrl);
                        
                    } catch (error) {
                        // Try with different approach or fallback
                        try {
                            const response = await fetch(`https://api.allorigins.win/get?url=${encodeURIComponent(normalizedUrl)}`);
                            const data = await response.json();
                            
                            if (data.status && data.status.http_code < 400) {
                                @this.call('createWebsite', normalizedUrl);
                            } else {
                                throw new Error('Website not accessible');
                            }
                        } catch (fallbackError) {
                            // Final fallback - just proceed anyway
                            @this.call('createWebsite', normalizedUrl);
                        }
                    }
                }
            }
        }
    </script>
</div>
