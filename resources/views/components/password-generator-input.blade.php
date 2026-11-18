@props([
    'name' => 'password',
    'id' => 'password',
    'label' => 'Login Password',
    'required' => false,
    'placeholder' => 'Leave blank to auto-generate & email',
    'hint' => 'If left blank, a secure password will be automatically generated and emailed.',
    'showSendEmailCheckbox' => true,
    'checkboxName' => 'send_welcome_email',
    'checkboxLabel' => 'Send Welcome Email with login credentials',
    'value' => '',
])

<div class="password-generator-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label for="{{ $id }}" class="form-label fw-medium mb-0">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @else
                <span class="badge bg-light text-muted border ms-1" style="font-size: 0.7rem; font-weight: 500;">Auto-generated if blank</span>
            @endif
        </label>
        <span class="text-muted small" style="font-size: 0.725rem;">
            <i class="bi bi-shield-lock me-1 text-primary"></i>Min 6 characters
        </span>
    </div>

    <div class="input-group">
        <input type="password"
               id="{{ $id }}"
               name="{{ $name }}"
               value="{{ old($name, $value) }}"
               class="form-control font-monospace @error($name) is-invalid @enderror"
               placeholder="{{ $placeholder }}"
               autocomplete="new-password"
               {{ $required ? 'required' : '' }}>

        <button type="button"
                class="btn btn-outline-dark"
                id="{{ $id }}_generate_btn"
                title="Automatically generate a secure password">
            <i class="bi bi-magic me-1"></i><span class="d-none d-sm-inline">Auto-</span>Generate
        </button>

        <button type="button"
                class="btn btn-outline-secondary"
                id="{{ $id }}_toggle_btn"
                title="Toggle password visibility">
            <i class="bi bi-eye" id="{{ $id }}_toggle_icon"></i>
        </button>

        <button type="button"
                class="btn btn-outline-secondary"
                id="{{ $id }}_copy_btn"
                title="Copy password to clipboard">
            <i class="bi bi-clipboard" id="{{ $id }}_copy_icon"></i>
        </button>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    <!-- Dynamic feedback banner when generated -->
    <div id="{{ $id }}_feedback" class="mt-1 d-none">
        <div class="d-flex align-items-center justify-content-between py-1 px-2 rounded border bg-light small">
            <span class="text-success fw-medium">
                <i class="bi bi-check-circle-fill me-1 text-success"></i> Generated:
                <code class="fw-bold px-1 py-0.5 rounded bg-white border text-dark" id="{{ $id }}_feedback_text"></code>
            </span>
            <span class="text-muted" style="font-size: 0.7rem;">Ready to be dispatched</span>
        </div>
    </div>

    @if($hint)
        <div class="form-text text-muted" style="font-size: 0.75rem;">
            {{ $hint }}
        </div>
    @endif

    @if($showSendEmailCheckbox)
        <div class="form-check mt-2">
            <input type="hidden" name="{{ $checkboxName }}" value="0">
            <input class="form-check-input"
                   type="checkbox"
                   name="{{ $checkboxName }}"
                   id="{{ $checkboxName }}"
                   value="1"
                   {{ old($checkboxName, '1') == '1' ? 'checked' : '' }}>
            <label class="form-check-label small fw-medium text-dark" for="{{ $checkboxName }}">
                <i class="bi bi-envelope-check text-primary me-1"></i>
                {{ $checkboxLabel }}
            </label>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function() {
    const input = document.getElementById('{{ $id }}');
    const genBtn = document.getElementById('{{ $id }}_generate_btn');
    const toggleBtn = document.getElementById('{{ $id }}_toggle_btn') || document.getElementById('togglePasswordBtn');
    const toggleIcon = document.getElementById('{{ $id }}_toggle_icon') || document.getElementById('togglePasswordIcon');
    const copyBtn = document.getElementById('{{ $id }}_copy_btn');
    const copyIcon = document.getElementById('{{ $id }}_copy_icon');
    const feedbackBox = document.getElementById('{{ $id }}_feedback');
    const feedbackText = document.getElementById('{{ $id }}_feedback_text');

    if (!input) return;

    function generateSecurePassword(length = 10) {
        const uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lowercase = 'abcdefghijkmnopqrstuvwxyz';
        const numbers = '23456789';
        const special = '@#$%&*!';

        const getRandom = (str) => str.charAt(Math.floor(Math.random() * str.length));

        let pwd = [
            getRandom(uppercase),
            getRandom(lowercase),
            getRandom(numbers),
            getRandom(special)
        ];

        const allChars = uppercase + lowercase + numbers + special;
        for (let i = pwd.length; i < length; i++) {
            pwd.push(getRandom(allChars));
        }

        // Fisher-Yates shuffle
        for (let i = pwd.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [pwd[i], pwd[j]] = [pwd[j], pwd[i]];
        }

        return pwd.join('');
    }

    if (genBtn) {
        genBtn.addEventListener('click', function() {
            const newPassword = generateSecurePassword(10);
            input.value = newPassword;
            input.type = 'text'; // Reveal generated password immediately for admin awareness

            if (toggleIcon) {
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }

            if (feedbackBox && feedbackText) {
                feedbackText.textContent = newPassword;
                feedbackBox.classList.remove('d-none');
            }

            // Also copy to clipboard for convenience
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(newPassword).then(function() {
                    if (copyIcon) {
                        copyIcon.classList.remove('bi-clipboard');
                        copyIcon.classList.add('bi-clipboard-check', 'text-success');
                        setTimeout(function() {
                            copyIcon.classList.remove('bi-clipboard-check', 'text-success');
                            copyIcon.classList.add('bi-clipboard');
                        }, 2500);
                    }
                }).catch(function() {});
            }
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            if (!input.value) {
                alert('No password entered or generated yet.');
                return;
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(function() {
                    copyIcon.classList.remove('bi-clipboard');
                    copyIcon.classList.add('bi-clipboard-check', 'text-success');
                    setTimeout(function() {
                        copyIcon.classList.remove('bi-clipboard-check', 'text-success');
                        copyIcon.classList.add('bi-clipboard');
                    }, 2500);
                });
            } else {
                input.select();
                document.execCommand('copy');
            }
        });
    }
})();
</script>
@endpush
