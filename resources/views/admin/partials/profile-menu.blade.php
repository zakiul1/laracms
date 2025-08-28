<div x-data="{ userDropdownIsOpen: false }" class="relative" x-on:keydown.esc.window="userDropdownIsOpen = false">

    <button type="button"
        class="flex w-full items-center rounded-radius gap-2 p-2 text-left text-on-surface hover:bg-primary/5 hover:text-on-surface-strong focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong dark:focus-visible:outline-primary-dark"
        x-bind:class="userDropdownIsOpen ? 'bg-primary/10 dark:bg-primary-dark/10' : ''" aria-haspopup="true"
        x-on:click="userDropdownIsOpen = ! userDropdownIsOpen" x-bind:aria-expanded="userDropdownIsOpen">

        <img src="https://penguinui.s3.amazonaws.com/component-assets/avatar-7.webp"
            class="size-8 object-cover rounded-radius" alt="avatar" aria-hidden="true" />
        <div class="hidden md:flex flex-col">
            <span class="text-sm font-bold text-on-surface-strong dark:text-on-surface-dark-strong">
                {{ auth()->user()->name ?? 'User' }}
            </span>
            <span class="text-xs" aria-hidden="true">{{ auth()->user()->email ?? '' }}</span>
            <span class="sr-only">profile settings</span>
        </div>
    </button>

    {{-- Dropdown --}}
    <div x-cloak x-show="userDropdownIsOpen"
        class="absolute top-14 right-0 z-20 h-fit w-48 border divide-y divide-outline border-outline bg-surface dark:divide-outline-dark dark:border-outline-dark dark:bg-surface-dark rounded-radius"
        role="menu" x-on:click.outside="userDropdownIsOpen = false" x-on:keydown.down.prevent="$focus.wrap().next()"
        x-on:keydown.up.prevent="$focus.wrap().previous()" x-transition x-trap="userDropdownIsOpen">

        <div class="flex flex-col py-1.5">
            <a href="{{ route('profile.edit') }}"
                class="flex items-center gap-2 px-2 py-1.5 text-sm font-medium text-on-surface underline-offset-2 hover:bg-primary/5 hover:text-on-surface-strong focus-visible:underline focus:outline-hidden dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong"
                role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0"
                    aria-hidden="true">
                    <path
                        d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
                </svg>
                <span>Profile</span>
            </a>
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-2 px-2 py-1.5 text-sm font-medium text-on-surface underline-offset-2 hover:bg-primary/5 hover:text-on-surface-strong focus-visible:underline focus:outline-hidden dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong"
                role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0"
                    aria-hidden="true">
                    <path
                        d="M13.92 3.845a19.362 19.362 0 0 1-6.3 1.98C6.765 5.942 5.89 6 5 6a4 4 0 0 0-.504 7.969 15.97 15.97 0 0 0 1.271 3.34c.397.771 1.342 1 2.05.59l.867-.5c.726-.419.94-1.32.588-2.02-.166-.331-.315-.666-.448-1.004 1.8.357 3.511.963 5.096 1.78A17.964 17.964 0 0 0 15 10c0-2.162-.381-4.235-1.08-6.155Z" />
                </svg>
                <span>User Dashboard</span>
            </a>
        </div>

        <div class="flex flex-col py-1.5">
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-2 px-2 py-1.5 text-sm font-medium text-on-surface underline-offset-2 hover:bg-primary/5 hover:text-on-surface-strong focus-visible:underline focus:outline-hidden dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong"
                role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0"
                    aria-hidden="true">
                    <path
                        d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm5-1v12h9a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zM4 2H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h2z" />
                </svg>
                <span>Admin</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full text-left flex items-center gap-2 px-2 py-1.5 text-sm font-medium text-on-surface underline-offset-2 hover:bg-primary/5 hover:text-on-surface-strong focus-visible:underline focus:outline-hidden dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong"
                    role="menuitem">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                        class="size-5 shrink-0" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M6 4.25A2.25 2.25 0 0 1 8.25 2.5h3.5A2.25 2.25 0 0 1 14 4.75v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-3.5a.75.75 0 0 0-.75.75v10.5c0 .414.336.75.75.75h3.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 11.75 18h-3.5A2.25 2.25 0 0 1 6 15.75V4.25Zm8.28 5.47a.75.75 0 0 0 0-1.06l-2.75-2.75a.75.75 0 1 0-1.06 1.06L12.94 8.5H8.75a.75.75 0 0 0 0 1.5h4.19l-2.22 2.22a.75.75 0 1 0 1.06 1.06l2.75-2.75Z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</div>
