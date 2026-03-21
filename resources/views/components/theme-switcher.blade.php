<div
    x-data="{
        darkModePreference: 'system', // 'system', 'on' or 'off',
        useLocalStorage: true, // true or false

        // Helper variables
        localStorageKey: 'dark-mode',

        // Initialize dark mode on component load
        init() {
            // Load preference from localStorage
            if (this.useLocalStorage) {
                this.darkModePreference = this.loadDarkModePreference();
            }

            // Apply dark mode immediately
            this.applyDarkMode();

            // Listen for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.darkModePreference === 'system') {
                    this.applyDarkMode();
                }
            });
        },

        // Load dark mode preference from localStorage
        loadDarkModePreference() {
            const stored = localStorage.getItem(this.localStorageKey);
            if (stored === 'on' || stored === 'off' || stored === 'system') {
                return stored;
            }
            return this.darkModePreference;
        },

        // Apply dark mode based on current preference
        applyDarkMode() {
            let darkModeActive;

            if (this.darkModePreference === 'system') {
                darkModeActive = window.matchMedia('(prefers-color-scheme: dark)').matches;
            } else {
                darkModeActive = this.darkModePreference === 'on';
            }

            document.documentElement.classList.toggle('dark', darkModeActive);
        },

        // Set dark mode preference
        setDarkMode(value) {
            this.darkModePreference = value;

            // Save preference to localStorage
            if (this.useLocalStorage) {
                localStorage.setItem(this.localStorageKey, value);
            }

            if (!document.startViewTransition) {
                this.applyDarkMode();
                return;
            }

            document.startViewTransition(() => {
                this.applyDarkMode();
            });
        }
    }"
>
    <div
        class="flex items-center justify-center rounded-lg border-mist-200 bg-mist-50 text-sm font-medium text-gray-400 dark:border-gray-700 dark:bg-gray-950/25 dark:text-gray-600"
    >
        <div class="inline-flex rounded-full bg-mist-100 p-1 ring-1 ring-mist-200/90 dark:bg-gray-950/50 dark:ring-gray-700/50">
            <div class="relative inline-flex items-center">
                <div
                    x-cloak
                    class="toggle-indicator absolute inset-y-0 left-0 w-1/3 rounded-full bg-white shadow-sm transition-transform duration-150 ease-out dark:bg-gray-700/75"
                    x-bind:class="{
                        'translate-x-0': darkModePreference === 'off',
                        'translate-x-full': darkModePreference === 'system',
                        'translate-x-[200%]': darkModePreference === 'on'
                    }"
                ></div>
                <label class="group relative flex">
                    <input
                        class="peer absolute start-0 top-0 appearance-none opacity-0"
                        id="dark-mode-off"
                        name="dark-mode-switch"
                        type="radio"
                        value="off"
                        x-bind:checked="darkModePreference === 'off'"
                        x-on:change="setDarkMode('off')"
                    />
                    <span
                        class="relative flex cursor-pointer items-center justify-center rounded-lg p-2 text-mist-500 transition-transform duration-150 ease-out peer-checked:text-mist-900 peer-focus-visible:ring-3 peer-focus-visible:ring-mist-200 hover:text-gray-900 active:scale-97 dark:text-gray-400 dark:peer-checked:text-white dark:peer-focus-visible:ring-gray-500/50 dark:hover:text-white"
                    >
                        <svg
                            class="lucide lucide-sun size-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                            height="24"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            width="24"
                        >
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 2v2"></path>
                            <path d="M12 20v2"></path>
                            <path d="m4.93 4.93 1.41 1.41"></path>
                            <path d="m17.66 17.66 1.41 1.41"></path>
                            <path d="M2 12h2"></path>
                            <path d="M20 12h2"></path>
                            <path d="m6.34 17.66-1.41 1.41"></path>
                            <path d="m19.07 4.93-1.41 1.41"></path>
                        </svg>
                        <span class="sr-only">Light mode</span>
                    </span>
                </label>
                <label class="group relative flex">
                    <input
                        class="peer absolute start-0 top-0 appearance-none opacity-0"
                        id="dark-mode-system"
                        name="dark-mode-switch"
                        type="radio"
                        value="system"
                        x-bind:checked="darkModePreference === 'system'"
                        x-on:change="setDarkMode('system')"
                    />
                    <span
                        class="relative flex cursor-pointer items-center justify-center rounded-lg p-2 text-mist-500 transition-transform duration-150 ease-out peer-checked:text-mist-900 peer-focus-visible:ring-3 peer-focus-visible:ring-mist-200 hover:text-gray-900 active:scale-97 dark:text-gray-400 dark:peer-checked:text-white dark:peer-focus-visible:ring-gray-500/50 dark:hover:text-white"
                    >
                        <svg
                            class="lucide lucide-monitor-smartphone size-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                            height="24"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            width="24"
                        >
                            <path d="M18 8V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h8"></path>
                            <path d="M10 19v-3.96 3.15"></path>
                            <path d="M7 19h5"></path>
                            <rect height="10" rx="2" width="6" x="16" y="12"></rect>
                        </svg>
                        <span class="sr-only">System preference</span>
                    </span>
                </label>
                <label class="group relative flex">
                    <input
                        class="peer absolute start-0 top-0 appearance-none opacity-0"
                        id="dark-mode-on"
                        name="dark-mode-switch"
                        type="radio"
                        value="on"
                        x-bind:checked="darkModePreference === 'on'"
                        x-on:change="setDarkMode('on')"
                    />
                    <span
                        class="relative flex cursor-pointer items-center justify-center rounded-lg p-2 text-mist-500 transition-transform duration-150 ease-out peer-checked:text-mist-900 peer-focus-visible:ring-3 peer-focus-visible:ring-mist-200 hover:text-gray-900 active:scale-97 dark:text-gray-400 dark:peer-checked:text-white dark:peer-focus-visible:ring-gray-500/50 dark:hover:text-white"
                    >
                        <svg
                            class="lucide lucide-moon size-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                            height="24"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            width="24"
                        >
                            <path
                                d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401"
                            ></path>
                        </svg>
                        <span class="sr-only">Dark mode</span>
                    </span>
                </label>
            </div>
        </div>
    </div>
</div>
