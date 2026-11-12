> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Input OTP

Accessible one-time password component with copy-paste functionality.

Showcase: `/admin/ui/input-otp`. Source: ui/components/InputOTP.svelte (source-only path: `../../resources/js/pages/ui/components/InputOTP.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/input-otp). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as InputOTP from '@/components/ui/input-otp';
    let value = $state('');
</script>

<InputOTP.Root maxlength={6} bind:value aria-label="Verification code">
    {#snippet children({ cells })}<InputOTP.Group>
            {#each cells as cell}<InputOTP.Slot {cell} />{/each}
        </InputOTP.Group>{/snippet}
</InputOTP.Root>

<p class="mt-3" role="status">Current value: {value || 'Empty'}</p>
```
