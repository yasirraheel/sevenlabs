@extends('layouts.app')

@section('css')
<link href="{{ asset('css/tts-custom.css') }}" rel="stylesheet">
@endsection

@section('content')
<!-- Hero Section -->
<div class="container-fluid home-cover">
      <div class="mb-4 position-relative custom-pt-6">
        <div class="container px-3 px-md-5">
          @if ($settings->announcement != '' && $settings->announcement_show == 'all'
              || $settings->announcement != '' && $settings->announcement_show == 'users' && auth()->check())
            <div class="alert alert-{{$settings->type_announcement}} announcements display-none alert-dismissible fade show" role="alert">
              <h4 class="alert-heading"><i class="bi-megaphone me-2"></i> {{ __('admin.announcements') }}</h4>
                    <p class="update-text">{!! $settings->announcement !!}</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" id="closeAnnouncements">
                  <i class="bi bi-x-lg"></i>
                </button>
                </div>
            @endif

            <div class="text-center text-md-start">
                <h1 class="display-4 display-md-3 fw-bold text-white mb-3">
                    <i class="bi bi-mic me-3"></i>SevenLabs Voice AI
                </h1>
                <p class="col-md-8 fs-5 fw-bold text-white mb-4">
                    Transform text into natural-sounding speech with advanced AI voice synthesis
                </p>
            </div>
        </div>
      </div>
</div>

<!-- Main TTS Interface -->
<div class="container-fluid py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">

                <!-- TTS Form -->
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-mic me-2"></i>Text-to-Speech Generator</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="ttsForm">
                            @csrf

                            <!-- Text Input -->
                            <div class="mb-4">
                                <label for="input" class="form-label fw-bold">
                                    <i class="bi bi-chat-text me-2"></i>Text to Convert
                                </label>
                                <textarea
                                    class="form-control"
                                    id="input"
                                    name="input"
                                    rows="4"
                                    placeholder="Enter the text you want to convert to speech..."
                                    required
                                ></textarea>
                                <div class="form-text">Enter the text you want to convert to speech</div>
                            </div>

                            <!-- Voice Selection -->
                            <div class="row mb-4">
                                <div class="col-12 col-md-6">
                                    <label for="voice_id" class="form-label fw-bold">
                                        <i class="bi bi-person-voice me-2"></i>Voice ID
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="voice_id"
                                        name="voice_id"
                                        placeholder="e.g., uju3wxzG5OhpWcoi3SMy"
                                        value="uju3wxzG5OhpWcoi3SMy"
                                        required
                                    >
                                    <div class="form-text">Enter the voice ID for the speech</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="model_id" class="form-label fw-bold">
                                        <i class="bi bi-cpu me-2"></i>Model
                                    </label>
                                    <select class="form-select" id="model_id" name="model_id" required>
                                        <option value="eleven_multilingual_v2">Eleven Multilingual v2</option>
                                        <option value="eleven_turbo_v2_5" selected>Eleven Turbo v2.5</option>
                                        <option value="eleven_flash_v2_5">Eleven Flash v2.5</option>
                                        <option value="eleven_v3">Eleven v3</option>
                                    </select>
                                    <div class="form-text">Select the AI model for voice generation</div>
                                </div>
                            </div>

                            <!-- Advanced Settings -->
                            <div class="accordion mb-4" id="advancedSettings">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdvanced">
                                            <i class="bi bi-gear me-2"></i>Advanced Settings
                                        </button>
                                    </h2>
                                    <div id="collapseAdvanced" class="accordion-collapse collapse" data-bs-parent="#advancedSettings">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <!-- Style -->
                                                <div class="col-12 col-md-6 mb-3">
                                                    <label for="style" class="form-label fw-bold">Style</label>
                                                    <input
                                                        type="range"
                                                        class="form-range"
                                                        id="style"
                                                        name="style"
                                                        min="0"
                                                        max="1"
                                                        step="0.1"
                                                        value="0"
                                                    >
                                                    <div class="d-flex justify-content-between">
                                                        <small>0.0</small>
                                                        <small id="styleValue">0.0</small>
                                                        <small>1.0</small>
                                                    </div>
                                                    <div class="form-text">Voice style variation (0.0 - 1.0)</div>
  </div>

                                                <!-- Speed -->
                                                <div class="col-12 col-md-6 mb-3">
                                                    <label for="speed" class="form-label fw-bold">Speed</label>
                                                    <input
                                                        type="range"
                                                        class="form-range"
                                                        id="speed"
                                                        name="speed"
                                                        min="0.7"
                                                        max="1.2"
                                                        step="0.1"
                                                        value="1"
                                                    >
                                                    <div class="d-flex justify-content-between">
                                                        <small>0.7x</small>
                                                        <small id="speedValue">1.0x</small>
                                                        <small>1.2x</small>
                                                    </div>
                                                    <div class="form-text">Speech speed (0.7 - 1.2)</div>
                                                </div>

                                                <!-- Similarity -->
                                                <div class="col-12 col-md-6 mb-3">
                                                    <label for="similarity" class="form-label fw-bold">Similarity</label>
                                                    <input
                                                        type="range"
                                                        class="form-range"
                                                        id="similarity"
                                                        name="similarity"
                                                        min="0"
                                                        max="1"
                                                        step="0.05"
                                                        value="0.75"
                                                    >
                                                    <div class="d-flex justify-content-between">
                                                        <small>0.0</small>
                                                        <small id="similarityValue">0.75</small>
                                                        <small>1.0</small>
                                                    </div>
                                                    <div class="form-text">Voice similarity (0.0 - 1.0)</div>
  </div>

                                                <!-- Stability -->
                                                <div class="col-12 col-md-6 mb-3">
                                                    <label for="stability" class="form-label fw-bold">Stability</label>
                                                    <input
                                                        type="range"
                                                        class="form-range"
                                                        id="stability"
                                                        name="stability"
                                                        min="0"
                                                        max="1"
                                                        step="0.05"
                                                        value="0.5"
                                                    >
                                                    <div class="d-flex justify-content-between">
                                                        <small>0.0</small>
                                                        <small id="stabilityValue">0.5</small>
                                                        <small>1.0</small>
                                                    </div>
                                                    <div class="form-text">Voice stability (0.0 - 1.0)</div>
  </div>

                                                <!-- Speaker Boost -->
                                                <div class="col-12 col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="use_speaker_boost" name="use_speaker_boost">
                                                        <label class="form-check-label fw-bold" for="use_speaker_boost">
                                                            Speaker Boost
                                                        </label>
                                                    </div>
                                                    <div class="form-text">Enhance speaker clarity</div>
                                                </div>

                                                <!-- Callback URL -->
                                                <div class="col-12 col-md-6 mb-3">
                                                    <label for="call_back_url" class="form-label fw-bold">Callback URL (Optional)</label>
                                                    <input
                                                        type="url"
                                                        class="form-control"
                                                        id="call_back_url"
                                                        name="call_back_url"
                                                        placeholder="https://your-domain.com/callback"
                                                    >
                                                    <div class="form-text">URL to receive completion notification</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
  </div>

                            <!-- Submit Button -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="generateBtn">
                                    <i class="bi bi-play-circle me-2"></i>Generate Speech
                                </button>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <a href="{{ url('tts/tasks') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-list-task me-2"></i>View Tasks
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
      </div>

                <!-- Results Section -->
                <div class="card shadow-lg border-0 mt-4" id="resultsCard" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-check-circle me-2"></i>Generated Audio</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center">
                            <audio controls class="w-100 mb-3" id="audioPlayer">
                                Your browser does not support the audio element.
                            </audio>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <button class="btn btn-outline-primary" id="downloadBtn">
                                    <i class="bi bi-download me-2"></i>Download Audio
                                </button>
                                <button class="btn btn-outline-secondary" id="resetBtn">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Generate New
                                </button>
                            </div>
                        </div>
                    </div>
  </div>

                <!-- Loading Spinner -->
                <div class="text-center mt-4" id="loadingSpinner" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Generating...</span>
    </div>
                    <p class="mt-2">Generating your speech, please wait...</p>
        </div>

          </div>
        </div>
      </div>
      </div>

<!-- Features Section -->
<div class="container-fluid py-5 bg-light">
      <div class="container">
        <div class="row text-center">
            <div class="col-12">
                <h2 class="mb-5">Why Choose SevenLabs Voice AI?</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-globe display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Multilingual Support</h5>
                        <p class="card-text">Generate speech in multiple languages with natural pronunciation and accent.</p>
                    </div>
              </div>
            </div>
            <div class="col-12 col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-lightning display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Fast Generation</h5>
                        <p class="card-text">Get high-quality speech output in seconds with our optimized AI models.</p>
          </div>
              </div>
            </div>
            <div class="col-12 col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-sliders display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Customizable</h5>
                        <p class="card-text">Fine-tune voice parameters including speed, style, and stability.</p>
          </div>
              </div>
            </div>
          </div>
        </div>
      </div>

@endsection

@section('javascript')
	<script type="text/javascript">
$(document).ready(function() {
    // Range slider value updates
    $('#style').on('input', function() {
        $('#styleValue').text($(this).val());
    });

    $('#speed').on('input', function() {
        $('#speedValue').text($(this).val() + 'x');
    });

    $('#similarity').on('input', function() {
        $('#similarityValue').text($(this).val());
    });

    $('#stability').on('input', function() {
        $('#stabilityValue').text($(this).val());
    });

    // TTS Form submission
    $('#ttsForm').on('submit', function(e) {
        e.preventDefault();

        // Show loading spinner
        $('#loadingSpinner').show();
        $('#resultsCard').hide();
        $('#generateBtn').prop('disabled', true);

        // Collect form data
        const formData = {
            input: $('#input').val(),
            voice_id: $('#voice_id').val(),
            model_id: $('#model_id').val(),
            style: parseFloat($('#style').val()),
            speed: parseFloat($('#speed').val()),
            use_speaker_boost: $('#use_speaker_boost').is(':checked'),
            similarity: parseFloat($('#similarity').val()),
            stability: parseFloat($('#stability').val()),
            call_back_url: $('#call_back_url').val() || null
        };

        // Check if API key is configured
        @if(!Helper::hasSevenLabsApiKey())
        alert('SevenLabs API key is not configured. Please contact administrator.');
        $('#loadingSpinner').hide();
        $('#generateBtn').prop('disabled', false);
        return;
        @endif

        // Make API call to GenAI Pro
        $.ajax({
            url: '{{ url("api/tts/generate") }}',
            method: 'POST',
            headers: {
                'Authorization': 'Bearer {{ Helper::getSevenLabsApiKey() }}',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success && response.task_id) {
                    // Task created successfully, start polling
                    pollTaskStatus(response.task_id);
                } else {
                    $('#loadingSpinner').hide();
                    $('#generateBtn').prop('disabled', false);
                    alert('Error: ' + (response.message || 'Failed to create task'));
                }
            },
            error: function(xhr) {
                $('#loadingSpinner').hide();
                $('#generateBtn').prop('disabled', false);

                let errorMessage = 'An error occurred while generating speech.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 401) {
                    errorMessage = 'API key is invalid or missing. Please check your SevenLabs API configuration.';
                } else if (xhr.status === 429) {
                    errorMessage = 'Rate limit exceeded. Please try again later.';
                }

                alert('Error: ' + errorMessage);
            }
        });
    });

    // Character counter for text input
    $('#input').on('input', function() {
        const text = $(this).val();
        const charCount = text.length;
        const maxChars = 5000; // Adjust based on API limits

        if (charCount > maxChars) {
            $(this).addClass('is-invalid');
            if (!$('#charCount').length) {
                $(this).after('<div id="charCount" class="invalid-feedback">Text exceeds maximum length of ' + maxChars + ' characters.</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $('#charCount').remove();
        }
    });

    // Auto-resize textarea
    $('#input').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Poll task status function
    function pollTaskStatus(taskId) {
        const maxAttempts = 60; // 60 seconds max wait
        let attempts = 0;

        const pollInterval = setInterval(function() {
            attempts++;

            $.ajax({
                url: '{{ url("api/tts/task") }}/' + taskId,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer {{ Helper::getSevenLabsApiKey() }}',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.task) {
                        const task = response.task;

                        if (task.status === 'completed' && task.result) {
                            // Task completed successfully
                            clearInterval(pollInterval);
                            $('#loadingSpinner').hide();
                            $('#generateBtn').prop('disabled', false);

                            // Show results
                            $('#audioPlayer').attr('src', task.result);
                            $('#resultsCard').show();

                            // Set download link
                            $('#downloadBtn').off('click').on('click', function() {
                                const link = document.createElement('a');
                                link.href = task.result;
                                link.download = 'generated_speech_' + Date.now() + '.mp3';
                                link.click();
                            });

                            // Reset form
                            $('#resetBtn').off('click').on('click', function() {
                                $('#ttsForm')[0].reset();
                                $('#resultsCard').hide();
                                $('#styleValue').text('0.0');
                                $('#speedValue').text('1.0x');
                                $('#similarityValue').text('0.75');
                                $('#stabilityValue').text('0.5');
                            });

                        } else if (task.status === 'failed') {
                            // Task failed
                            clearInterval(pollInterval);
                            $('#loadingSpinner').hide();
                            $('#generateBtn').prop('disabled', false);
                            alert('Task failed: ' + (task.error || 'Unknown error'));

                        } else if (attempts >= maxAttempts) {
                            // Timeout
                            clearInterval(pollInterval);
                            $('#loadingSpinner').hide();
                            $('#generateBtn').prop('disabled', false);
                            alert('Task timeout - please try again later');
                        }
                        // If status is 'pending' or 'processing', continue polling
                    } else {
                        // Error in response
                        clearInterval(pollInterval);
                        $('#loadingSpinner').hide();
                        $('#generateBtn').prop('disabled', false);
                        alert('Error checking task status: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    clearInterval(pollInterval);
                    $('#loadingSpinner').hide();
                    $('#generateBtn').prop('disabled', false);
                    alert('Error checking task status: ' + (xhr.responseJSON?.message || 'Network error'));
                }
            });
        }, 1000); // Poll every 1 second
    }

    // Check for cached results on page load
    function checkCachedResults() {
        // This would check for any cached results from callbacks
        // Implementation depends on your caching strategy
    }
});

// Session messages
		@if (session('success_verify'))
		swal({
			title: "{{ __('misc.welcome') }}",
			text: "{{ __('users.account_validated') }}",
			type: "success",
			confirmButtonText: "{{ __('users.ok') }}"
			});
		@endif

		@if (session('error_verify'))
		swal({
			title: "{{ __('misc.error_oops') }}",
			text: "{{ __('users.code_not_valid') }}",
			type: "error",
			confirmButtonText: "{{ __('users.ok') }}"
			});
		@endif
	</script>
@endsection
