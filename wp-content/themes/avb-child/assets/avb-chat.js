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
                
            // NOTE: More cases would be needed here to capture the full tree (Generation 2, 3, etc.)
            // For the demo, we jump directly to email capture after one ancestor.
            
            case 3: // Capturing Birth Location
                if (userInput) {
                    ancestorData.ancestor_1_location = userInput;
                    displayMessage("Thank you! I have the data needed for the viability check. What is your best email address to send the results?", 'agent');
                    conversationStep++; 
                    break;
                }
                displayMessage("Please provide the birth location.", 'agent');
                break;

            case 4: // Capturing Final Email and Triggering API Call
                if (userInput && userInput.includes('@')) {
                    ancestorData.client_email = userInput;
                    displayMessage("Processing your request... Please wait while I check the data viability...", 'agent');
                    inputField.disabled = true; // Disable input while API processes

                    // --- FULL STACK ACTION: ASYNCHRONOUS API CALL ---
                    // Here is where the code connects to the PHP endpoint (Paso 3.4)
                    
                    // For the demo, we simulate success for now:
                    setTimeout(() => {
                        displayMessage(`Success! Your Viability Report results will be sent to ${userInput}. An agent will contact you soon.`, 'agent');
                        inputField.disabled = false;
                        conversationStep = 10; // End state
                    }, 2500); 
                    // End of simulated API call
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