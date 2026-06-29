<x-app-layout>
    <livewire:dashboard.overview />

    <!-- Clock display auto-updating JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const clockEl = document.getElementById('clock-display');
            if (clockEl) {
                setInterval(() => {
                    const now = new Date();
                    clockEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                }, 1000);
            }
        });
    </script>
</x-app-layout>
