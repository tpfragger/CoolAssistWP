jQuery(document).ready(function($) {
    var chatMessages = $('#chat-messages');
    var userInput = $('#user-message');
    var chatForm = $('#chat-form');
    var modelSelect = $('#model-number-select');
    var typingIndicator = $('#typing-indicator');
    var isProcessing = false;
    var selectedModel = '';
    var currentIssue = '';
    var questionLayer = 0;

    // Initial predefined questions
    var initialQuestions = [
        "Insufficient cooling or heating",
        "Unusual noises during operation",
        "Water leakage from indoor unit",
        "System short cycling",
        "Compressor not starting",
        "Frozen evaporator coil",
        "Condenser unit issues",
        "Thermostat malfunctions"
    ];

    // Nested follow-up questions (2 layers)
    var followUpQuestions = {
        "Insufficient cooling or heating": {
            "Is the airflow weak?": [
                "Have you checked and cleaned the air filter?",
                "Are any vents or registers blocked?",
                "Is the fan speed set correctly?"
            ],
            "Are there temperature inconsistencies across rooms?": [
                "Have you checked for air leaks in ductwork?",
                "Are all vents open and unobstructed?",
                "Is the thermostat located in an appropriate area?"
            ],
            "Is the system running constantly without reaching set temperature?": [
                "Have you checked the refrigerant levels?",
                "Is the outdoor unit clean and unobstructed?",
                "Have you inspected the compressor for issues?"
            ],
            "Have you checked the air filter condition?": [
                "When was the last time you replaced the filter?",
                "Is the filter the correct size and type for your system?",
                "Do you notice any debris or damage on the filter?"
            ]
        },
        "Unusual noises during operation": {
            "Is it a buzzing or humming sound?": [
                "Does the sound come from the indoor or outdoor unit?",
                "Is the sound constant or intermittent?",
                "Have you checked for loose parts or vibrations?"
            ],
            "Do you hear a rattling or clanking noise?": [
                "Is the noise present in both cooling and heating modes?",
                "Have you inspected the fan blades for damage?",
                "Are any mounting brackets or screws loose?"
            ],
            "Is there a hissing or whistling sound?": [
                "Does the sound occur during startup or shutdown?",
                "Have you checked for refrigerant leaks?",
                "Is the sound coming from vents or the unit itself?"
            ],
            "Does the noise come from the indoor or outdoor unit?": [
                "Is the noise more noticeable at certain times of day?",
                "Have you noticed any changes in performance along with the noise?",
                "Can you pinpoint a specific component as the source?"
            ]
        },
        "Water leakage from indoor unit": {
            "Is the leakage constant or intermittent?": [
                "Does the leakage coincide with AC operation?",
                "Have you noticed any changes in humidity levels?",
                "Is there any frost or ice formation visible?"
            ],
            "Have you checked the condensate drain line for blockages?": [
                "When was the last time the drain line was cleaned?",
                "Is the drain line properly sloped for water flow?",
                "Have you tried using a wet/dry vacuum to clear the line?"
            ],
            "Is the unit level and properly installed?": [
                "Have you used a level to check the unit's installation?",
                "Are there any signs of settling or shifting?",
                "Is the condensate pan aligned correctly?"
            ],
            "Do you see any ice formation on the evaporator coil?": [
                "Is the AC running for long periods without shutting off?",
                "Have you checked the air filter for blockages?",
                "Are the refrigerant levels correct?"
            ]
        },
        "System short cycling": {
            "Is the system turning on and off frequently?": [
                "How often does the system cycle on and off?",
                "Have you noticed any changes in room temperature during cycling?",
                "Is the cycling more frequent during specific times of day?"
            ],
            "Have you checked the thermostat settings?": [
                "Is the temperature differential set correctly?",
                "Have you calibrated the thermostat recently?",
                "Is the thermostat located away from heat sources or drafts?"
            ],
            "Is the air filter clean and unobstructed?": [
                "When was the last time you changed or cleaned the filter?",
                "Is the filter the correct size for your system?",
                "Have you noticed any debris around the filter area?"
            ],
            "Have you inspected the refrigerant levels?": [
                "Are there any signs of refrigerant leaks?",
                "When was the last time the system was recharged?",
                "Have you noticed any ice formation on the refrigerant lines?"
            ]
        },
        "Compressor not starting": {
            "Is there any humming or clicking sound when the system tries to start?": [
                "Does the sound come from the outdoor unit?",
                "Is the sound constant or intermittent?",
                "Have you checked the capacitor?"
            ],
            "Have you checked the power supply to the compressor?": [
                "Is the disconnect switch in the ON position?",
                "Have you inspected the circuit breaker?",
                "Are all electrical connections secure?"
            ],
            "Is the thermostat calling for cooling?": [
                "Have you tested the thermostat's functionality?",
                "Is the set temperature lower than the room temperature?",
                "Have you checked the thermostat wiring?"
            ],
            "Have you inspected the contactor?": [
                "Is the contactor pulling in when the thermostat calls for cooling?",
                "Are the contactor points clean and making good contact?",
                "Have you checked the voltage across the contactor coil?"
            ]
        },
        "Frozen evaporator coil": {
            "Is there visible ice on the coil or refrigerant lines?": [
                "How much of the coil is covered in ice?",
                "Is the ice formation consistent or patchy?",
                "Have you noticed any water leakage as a result?"
            ],
            "Have you checked the airflow across the coil?": [
                "Is the air filter clean and unobstructed?",
                "Are all supply and return vents open and clear?",
                "Have you inspected the blower motor and fan?"
            ],
            "Are the refrigerant levels correct?": [
                "When was the last time the system was charged?",
                "Have you noticed any signs of refrigerant leaks?",
                "Has the system's cooling performance decreased recently?"
            ],
            "Is the outdoor temperature very low?": [
                "Are you running the AC when outdoor temperatures are below 60°F (15°C)?",
                "Is the system equipped with a low ambient kit?",
                "Have you considered using alternative cooling methods in cooler weather?"
            ]
        },
        "Condenser unit issues": {
            "Is the condenser fan running?": [
                "Do you hear the fan motor running but the blade isn't spinning?",
                "Is there any unusual noise coming from the fan motor?",
                "Have you checked the capacitor for the fan motor?"
            ],
            "Is the condenser coil clean and unobstructed?": [
                "When was the last time the condenser coil was cleaned?",
                "Are there any objects blocking airflow around the unit?",
                "Have you noticed any bent fins on the condenser coil?"
            ],
            "Are there any unusual noises coming from the condenser unit?": [
                "Can you describe the noise (e.g., rattling, humming, hissing)?",
                "Does the noise change in intensity or frequency?",
                "Have you inspected for any loose components or debris?"
            ],
            "Is the unit level and stable?": [
                "Have you noticed any settling or shifting of the concrete pad?",
                "Are all mounting bolts secure?",
                "Is there any vibration when the unit is running?"
            ]
        },
        "Thermostat malfunctions": {
            "Is the display on the thermostat blank or showing errors?": [
                "Have you checked the batteries in the thermostat?",
                "Is there power to the thermostat from the system?",
                "Have you tried resetting the thermostat?"
            ],
            "Is the thermostat not responding to temperature changes?": [
                "Have you calibrated the thermostat recently?",
                "Is the thermostat located in an area with stable temperature?",
                "Have you checked if the temperature sensor is functioning correctly?"
            ],
            "Are there programming issues with the thermostat?": [
                "Have you reviewed the programmed schedule?",
                "Are the time and date settings correct?",
                "Have you checked if the thermostat is in the correct mode (cool/heat/auto)?"
            ],
            "Is there a communication issue between the thermostat and the HVAC system?": [
                "Have you inspected the wiring between the thermostat and the system?",
                "Are all connections secure at both the thermostat and control board?",
                "Have you checked for any error codes on the system's control board?"
            ]
        }
    };

    // Display initial questions
    displayInitialQuestions();

    // Populate model number dropdown
    $.ajax({
        url: coolassist_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'coolassist_get_model_numbers',
            nonce: coolassist_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                $.each(response.data, function(index, modelNumber) {
                    modelSelect.append($('<option></option>').val(modelNumber).text(modelNumber));
                });
            }
        }
    });

    function displayInitialQuestions() {
        var buttonsHtml = '<div class="chat-buttons">';
        initialQuestions.forEach(function(question) {
            buttonsHtml += '<button class="chat-button" data-action="initial-question">' + question + '</button>';
        });
        buttonsHtml += '</div>';
        chatMessages.append('<div class="ai-message">Please select your issue or type your question:</div>' + buttonsHtml);
    }

    // Handle question button clicks
    $(document).on('click', '.chat-button', function() {
        var question = $(this).text();
        var action = $(this).data('action');

        if (action === 'initial-question') {
            handleInitialQuestion(question);
        } else {
            handleFollowUpQuestion(question);
        }
    });

    function handleInitialQuestion(question) {
        if (!selectedModel) {
            alert("Please select an AC model before proceeding.");
            return;
        }

        currentIssue = question;
        questionLayer = 0;
        displayMessage('User', question);

        if (followUpQuestions[question]) {
            displayFollowUpQuestions(question);
        } else {
            sendToAI(question);
        }
    }

    function displayFollowUpQuestions(question) {
        var buttonsHtml = '<div class="chat-buttons">';
        var currentQuestions = questionLayer === 0 ? Object.keys(followUpQuestions[question]) : followUpQuestions[currentIssue][question];
        
        currentQuestions.forEach(function(q) {
            buttonsHtml += '<button class="chat-button" data-action="follow-up">' + q + '</button>';
        });
        buttonsHtml += '</div>';
        chatMessages.append(buttonsHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function handleFollowUpQuestion(answer) {
        displayMessage('User', answer);
        questionLayer++;

        if (questionLayer < 2 && followUpQuestions[currentIssue][answer]) {
            displayFollowUpQuestions(answer);
        } else {
            var fullQuery = 'Issue: ' + currentIssue + '\nDetails: ' + answer;
            sendToAI(fullQuery);
        }
    }

    // Model number selection
    modelSelect.on('change', function() {
        selectedModel = $(this).val();
        if (selectedModel) {
            displayMessage('System', 'Selected AC Model: ' + selectedModel);
        }
    });

    // Image upload handling
    $('#image-upload').on('change', function(e) {
        if (isProcessing) return;

        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result).show();
                sendMessage('', file);
            }
            reader.readAsDataURL(file);
        }
    });

    // Chat form handling
    chatForm.on('submit', function(e) {
        e.preventDefault();
        if (isProcessing) return;

        var message = userInput.val().trim();
        var imageFile = $('#image-upload')[0].files[0];

        if (message === '' && !imageFile) {
            alert('Please enter a message or upload an image.');
            return;
        }

        sendMessage(message, imageFile);
    });

    function sendMessage(message, imageFile = null) {
        isProcessing = true;
        showTypingIndicator();
        disableInputs();

        var formData = new FormData();
        formData.append('action', 'coolassist_chat');
        formData.append('nonce', coolassist_ajax.nonce);
        formData.append('message', message);
        formData.append('model_number', selectedModel);
        if (imageFile) {
            formData.append('image', imageFile);
        }

        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    if (imageFile) {
                        displayMessage('User', '<img src="' + response.data.image_url + '" alt="Uploaded Image" style="max-width: 100%; height: auto;">');
                    }
                    if (message.trim() !== '') {
                        displayMessage('User', message);
                    }
                    displayMessage('AI', formatAIResponse(response.data.message));
                    if (response.data.manual_images) {
                        displayManualImages(response.data.manual_images);
                    }
                    if (response.data.buttons) {
                        displayButtons(response.data.buttons);
                    }
                } else {
                    displayMessage('AI', 'Error: ' + (response.data || 'Unable to process your request. Please try again.'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                displayMessage('AI', 'Error: Unable to communicate with the server. Please try again later.');
            },
            complete: function() {
                isProcessing = false;
                hideTypingIndicator();
                enableInputs();
                userInput.val('');
                $('#image-upload').val('');
            }
        });
    }

    function sendToAI(query) {
        sendMessage(query);
    }

    function displayMessage(sender, message) {
        var messageHtml = '<div class="chat-message ' + (sender === 'User' ? 'user-message' : 'ai-message') + '">' +
                          '<strong>' + sender + ':</strong> ' + message + '</div>';
        chatMessages.append(messageHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function formatAIResponse(message) {
        var paragraphs = message.split('\n\n');
        var formattedParagraphs = paragraphs.map(function(paragraph) {
            if (paragraph.includes('\n- ')) {
                var listItems = paragraph.split('\n- ');
                var listHtml = '<ul>';
                listItems.forEach(function(item, index) {
                    if (index > 0) {
                        listHtml += '<li>' + item + '</li>';
                    }
                });
                listHtml += '</ul>';
                return listHtml;
            } else {
                return '<p>' + paragraph + '</p>';
            }
        });

        return formattedParagraphs.join('');
    }

    function displayManualImages(images) {
        images.forEach(function(image) {
            var imageHtml = '<div class="manual-image"><img src="' + image.url + '" alt="Manual Reference"><p>' + image.caption + '</p></div>';
            chatMessages.append(imageHtml);
        });
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function displayButtons(buttons) {
        var buttonsHtml = '<div class="chat-buttons">';
        buttons.forEach(function(button) {
            buttonsHtml += '<button class="chat-button" data-action="' + button.action + '">' + button.text + '</button>';
        });
        buttonsHtml += '</div>';
        chatMessages.append(buttonsHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function showTypingIndicator() {
        typingIndicator.show();
    }

    function hideTypingIndicator() {
        typingIndicator.hide();
    }

    function disableInputs() {
        userInput.prop('disabled', true);
        chatForm.find('button[type="submit"]').prop('disabled', true);
        $('#image-upload').prop('disabled', true);
    }

    function enableInputs() {
        userInput.prop('disabled', false);
        chatForm.find('button[type="submit"]').prop('disabled', false);
        $('#image-upload').prop('disabled', false);
    }

    // Login form handling
    $('#coolassist-login-form').on('submit', function(e) {
        e.preventDefault();
        $('#login-loading').show();
        var formData = $(this).serialize();
        formData += '&action=coolassist_login&nonce=' + coolassist_ajax.nonce;

        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Full login response:', response);
                $('#login-loading').hide();
                if (response.success) {
                    $('#login-message').html('<p class="success">' + response.data.message + '</p>');
                    console.log('Login successful, reloading page');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $('#login-message').html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Login error:', status, error);
                $('#login-loading').hide();
                $('#login-message').html('<p class="error">Login error: ' + error + '</p>');
            }
        });
    });

    // Logout functionality
    $('#coolassist-logout').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'coolassist_logout',
                nonce: coolassist_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Logout successful, reloading page');
                    location.reload();
                } else {
                    console.error('Logout failed:', response.data);
                    alert('Logout failed. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Logout error:', status, error);
                alert('An error occurred during logout. Please try again.');
            }
        });
    });

    // Load chat history on page load
    loadChatHistory();

    function saveChatHistory() {
        var chatHistory = chatMessages.html();
        sessionStorage.setItem('chatHistory', chatHistory);
    }

    function loadChatHistory() {
        var chatHistory = sessionStorage.getItem('chatHistory');
        if (chatHistory) {
            chatMessages.html(chatHistory);
        } else {
            displayWelcomeMessage();
        }
    }

        displayMessage('AI', 'Welcome to CoolAssist! Please select an AC model from the dropdown menu above, and I\'ll be ready to assist you with technical information and troubleshooting steps.');
    

    // Save chat history before unloading the page
    $(window).on('beforeunload', function() {
        saveChatHistory();
    });
});
