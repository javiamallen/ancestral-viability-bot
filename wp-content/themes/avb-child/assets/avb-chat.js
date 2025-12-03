// --- JAVASCRIPT LOGIC FOR ANCESTRAL VIABILITY BOT (AVB) ---

document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.getElementById('avb-chat-container');
    const floatingButton = document.getElementById('avb-floating-button');
    const dialogueArea = document.getElementById('avb-dialogue-area');
    const inputField = document.getElementById('avb-input');
    const sendButton = document.getElementById('avb-send');

    // State Variables for the conversation flow
    let conversationStep = 0;
    let ancestorData = {}; // Stores the temporary family tree data
    let sessionId = 'avb-' + Math.random().toString(36).substring(2, 9); // Generate unique session ID

    // Security and API URL from wp_localize_script (functions.php)
    const restUrl = avb_rest_params.rest_url;
    const nonce = avb_rest_params.nonce;

    // --- Core Functions ---

    // 1. Function to display a message in the chat area
    function displayMessage(text, sender) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('avb-message', `avb-${sender}`);
        messageElement.textContent = text;
        dialogueArea.appendChild(messageElement);
        dialogueArea.scrollTop = dialogueArea.scrollHeight; // Auto-scroll to the bottom
    }

    // 2. Chatbot Dialog Flow
    function nextStep(userInput = null) {
        // --- DATA ENTRY CONVERSATIONAL FLOW (4 Stages) ---
        
        switch (conversationStep) {
            case 0:
                displayMessage("Hello! I'm the Ancestral Viability Bot. I will guide you to map your family tree for your legal claim. What is your name?", 'agent');
                conversationStep++;
                break;

            case 1: // Capturing Name
                if (userInput) {
                    ancestorData.client_name = userInput;
                    displayMessage(`Nice to meet you, ${userInput}. Now, let's start with your first ancestor. What was your father's or mother's name? (Name and Surname)`, 'agent');
                    conversationStep++;
                    break;
                }
                displayMessage("Please provide your name to continue.", 'agent');
                break;
            
            case 2: // Capturing First Ancestor (Generation 1)
                if (userInput) {
                    ancestorData.ancestor_1_name = userInput;
                    displayMessage(`Got it: ${userInput}. What was their specific place of birth (City and Country)? This is critical for legal validation.`, 'agent');
                    conversationStep++;
                    break;
                }
                displayMessage("Please provide the name of the ancestor to continue.", 'agent');
                break;
                
            case 3: // Capturing Birth Location
                if (userInput) {
                    ancestorData.ancestor_1_location = userInput;
                    displayMessage("Thank you! I have the data needed for the viability check. What is your best email address to send the results?", 'agent');
                    conversationStep++; 
                    break;
                }
                displayMessage("Please provide the birth location.", 'agent');
                break;

            case 4: // Capturing Final Email and Triggering API Call (REAL LOGIC)
                if (userInput && userInput.includes('@')) {
                    ancestorData.client_email = userInput;
                    displayMessage("Processing your request... Please wait while I perform the viability check...", 'agent');
                    inputField.disabled = true; // Disable input while API processes

                    // --- FULL STACK ACTION: ASYNCHRONOUS API CALL ---
                    sendDataToAPI(ancestorData); 
                    
                    break;
                }
                displayMessage("Please enter a valid email address.", 'agent');
                break;

            default:
                displayMessage("Type 'Hello' to restart the conversation.", 'agent');
                conversationStep = 0;
                break;
        }
    }


    // --- NEW FUNCTION: SEND DATA TO PHP BACK END ---
    async function sendDataToAPI(data) {
        // Construct the payload with required data fields for the PHP Endpoint (Paso 2.2)
        const payload = {
            session_id: sessionId,
            generation: 1, // Simplifying to the first generation for the demo
            relation_type: 'Father/Mother',
            name: data.ancestor_1_name,
            birth_location: data.ancestor_1_location,
            birth_date: 'Unknown',
            client_email: data.client_email,
        };
        
        const apiUrl = avb_rest_params.rest_url;
        const nonce = avb_rest_params.nonce;

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce // CRITICAL SECURITY HEADER (Paso 2.4)
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (response.ok) {
                // SUCCESS: Data inserted into DB and WebHook triggered
                displayMessage("Success! Your family connection data has been saved and the viability check has started. An analyst will contact you soon.", 'agent');
            } else {
                // FAILURE: Database or Validation Error from PHP Back End
                displayMessage(`Connection Error (${response.status}). Please try again later.`, 'agent');
                console.error('API Error Response:', result);
            }
        } catch (error) {
            // CATCH: Network failure or PHP fatal error
            displayMessage("Network Error: Could not connect to the viability service. Please contact support.", 'agent');
            console.error('Network or Uncaught Error:', error);
        } finally {
            inputField.disabled = false; // Re-enable input (for restart command)
            conversationStep = 10; // End state
        }
    }


    // --- Event Handlers ---

    // 3. Toggle Chat Visibility
    floatingButton.addEventListener('click', function() {
        if (chatContainer.style.display === 'flex') {
            chatContainer.style.display = 'none';
        } else {
            chatContainer.style.display = 'flex';
            // Start the conversation flow when chat is opened
            if (conversationStep === 0) {
                nextStep();
            }
        }
    });

    // 4. Send Message Handler
    sendButton.addEventListener('click', function() {
        sendMessage();
    });

    inputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function sendMessage() {
        const userInput = inputField.value.trim();
        if (userInput !== "") {
            displayMessage(userInput, 'user');
            inputField.value = ''; // Clear input field
            nextStep(userInput); // Process the user's message
        }
    }

}); // End of DOMContentLoaded