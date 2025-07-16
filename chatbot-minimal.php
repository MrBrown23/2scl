<?php 
  $language = $_SESSION['language'] ?? 'fr';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chatbot Demo</title>
  <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,0,0&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,1,0" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Arial', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      /* height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center; */

    }

    .demo-content {
      text-align: center;
      padding: 2rem;
    }

    .demo-content h1 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    .demo-content p {
      font-size: 1.2rem;
      opacity: 0.9;
    }

    /* Chatbot Styles */
    #chatbot-toggler {
      position: fixed;
      bottom: 65px;
      right: 35px;
      border: none;
      height: 55px;
      width: 55px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border-radius: 50%;
      background: #4CAF50;
      transition: all 0.2s ease;
      z-index: 1000;
    }

    body.show-chatbot #chatbot-toggler {
      transform: rotate(90deg);
    }

    #chatbot-toggler span {
      color: #fff;
      position: absolute;
    }

    body.show-chatbot #chatbot-toggler span:first-child,
    #chatbot-toggler span:last-child {
      opacity: 0;
    }

    body.show-chatbot #chatbot-toggler span:last-child {
      opacity: 1;
    }

    .chatbot-popup {
      position: fixed;
      right: 35px;
      bottom: 45px;
      width: 375px;
      height: 590px;
      background: #fff;
      overflow: hidden;
      border-radius: 15px;
      opacity: 0;
      transform: scale(0.2);
      transform-origin: bottom right;
      pointer-events: none;
      box-shadow: 0 0px 120px 0 rgba(0, 0, 0, 0.5), 0 32px 64px -48px rgba(0, 0, 0, 0.5);
      transition: all 0.1s ease;
      z-index: 9999;
    }

    body.show-chatbot .chatbot-popup {
      opacity: 1;
      pointer-events: auto;
      transform: scale(1);
    }

    .chat-header {
      display: flex;
      align-items: center;
      background: #4CAF50;
      padding: 15px 22px;
      justify-content: space-between;
    }

    .chat-header .header-info {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .header-info .chatbot-logo {
      height: 35px;
      width: 35px;
      padding: 6px;
      fill: #4CAF50;
      flex-shrink: 0;
      background: #fff;
      border-radius: 50%;
    }

    .header-info .logo-text {
      color: #fff;
      font-size: 1.31rem;
      font-weight: 600;
    }

    .chat-header #close-chatbot {
      border: none;
      color: #fff;
      height: 40px;
      width: 40px;
      font-size: 1.9rem;
      margin-right: -10px;
      padding-top: 2px;
      cursor: pointer;
      border-radius: 50%;
      background: none;
      transition: 0.2s ease;
    }

    .chat-header #close-chatbot:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .chat-body {
      padding: 25px 22px;
      display: flex;
      gap: 20px;
      height: 460px;
      margin-bottom: 82px;
      overflow-y: auto;
      flex-direction: column;
      scrollbar-width: thin;
      scrollbar-color: #ccccf5 transparent;
    }

    .chat-body .message {
      display: flex;
      gap: 11px;
      align-items: center;
    }

    .chat-body .bot-message .bot-avatar {
      height: 35px;
      width: 35px;
      padding: 6px;
      fill: #fff;
      flex-shrink: 0;
      margin-bottom: 20px;
      align-self: flex-end;
      background: #4CAF50;
      border-radius: 50%;
    }

    .chat-body .user-message {
      flex-direction: column;
      align-items: flex-end;
    }

    .chat-body .message .message-text {
      padding: 12px 16px;
      max-width: 75%;
      font-size: 0.95rem;
      background: #f2f2ff;
      color: #333;
    }

    .chat-body .bot-message.thinking .message-text {
      padding: 2px 16px;
    }

    .chat-body .bot-message .message-text {
      background: #f2f2ff;
      border-radius: 13px 13px 13px 13px;
    }

    .chat-body .user-message .message-text {
      color: #fff;
      background: #4CAF50;
      border-radius: 13px 13px 3px 13px;
    }

    .chat-body .bot-message .thinking-indicator {
      display: flex;
      gap: 4px;
      padding-block: 15px;
    }

    .chat-body .bot-message .thinking-indicator .dot {
      height: 7px;
      width: 7px;
      opacity: 0.7;
      border-radius: 50%;
      background: #4CAF50;
      animation: dotPulse 1.8s ease-in-out infinite; 
    }

    .chat-body .bot-message .thinking-indicator .dot:nth-child(1) {
      animation-delay: 0.2s;
    }

    .chat-body .bot-message .thinking-indicator .dot:nth-child(2) {
      animation-delay: 0.3s;
    }

    .chat-body .bot-message .thinking-indicator .dot:nth-child(3) {
      animation-delay: 0.4s;
    }

    @keyframes dotPulse {
      0%, 44% {
        transform: translateY(0);
      }
      28% {
        opacity: 0.4;
        transform: translateY(-4px);
      }
      44% {
        opacity: 0.2;
      }
    }

    .chat-footer {
      position: absolute;
      bottom: 0;
      width: 100%;
      background: #fff;
      padding: 15px 22px 20px;
    }

    .chat-footer .chat-form {
      display: flex;
      position: relative;
      align-items: center;
      background: #fff;
      border-radius: 32px;
      outline: 1px solid #cccce5;
    }

    .chat-footer .chat-form:focus-within {
      outline: 2px solid #4CAF50;
    }

    .chat-form .message-input {
      border: none;
      outline: none;
      height: 47px;
      width: 100%;
      resize: none;
      max-height: 180px;
      white-space: pre-line;
      font-size: 0.95rem;
      padding: 14px 0 13px 18px;
      border-radius: inherit;
      scrollbar-width: thin;
      scrollbar-color: transparent transparent;
    }

    .chat-form .message-input:hover {
      scrollbar-color: #ccccf5 transparent;
    }

    .chat-form .chat-controls {
      display: flex;
      height: 47px;
      gap: 3px;
      align-items: center;
      align-self: flex-end;
      padding-right: 6px;
    }

    .chat-form .chat-controls button {
      height: 35px;
      width: 35px;
      border: none;
      font-size: 1.15rem;
      cursor: pointer;
      color: #706db0;
      background: none;
      border-radius: 50%;
      transition: 0.2s ease;
    }

    .chat-form .chat-controls #send-message {
      color: #fff;
      display: none;
      background: #4CAF50;
    }

    .chat-form .message-input:valid ~ .chat-controls #send-message {
      display: block;
    }

    .chat-form .chat-controls #send-message:hover {
      background: #45a049;
    }

    .chat-form .chat-controls button:hover {
      background: #cfcfd1;
    }
  </style>
</head>
<body class="flex flex-col">
  <div class='h-full w-full'>
    <?php 
        $page_title = 'Chatbot';
        include 'header.php';
    ?>
  </div>
  <div class='flex h-[90vh] justify-center items-center'>
      <div class="demo-content text-white">
        <h1>Chatbot Demo</h1>
        <p>Cliquez sur le bouton « chat » en bas à droite pour entamer une conversation !</p>
      </div>

  <!-- Chatbot Toggle Button -->
  <button id="chatbot-toggler">
    <span class="material-symbols-rounded">mode_comment</span>
    <span class="material-symbols-rounded">close</span>
  </button>

  <!-- Chatbot Popup -->
  <div class="chatbot-popup">
    <!-- Chatbot Header -->
    <div class="chat-header">
      <div class="header-info">
        <svg class="chatbot-logo" xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 1024 1024">
          <path d="M738.3 287.6H285.7c-59 0-106.8 47.8-106.8 106.8v303.1c0 59 47.8 106.8 106.8 106.8h81.5v111.1c0 .7.8 1.1 1.4.7l166.9-110.6 41.8-.8h117.4l43.6-.4c59 0 106.8-47.8 106.8-106.8V394.5c0-59-47.8-106.9-106.8-106.9zM351.7 448.2c0-29.5 23.9-53.5 53.5-53.5s53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5-53.5-23.9-53.5-53.5zm157.9 267.1c-67.8 0-123.8-47.5-132.3-109h264.6c-8.6 61.5-64.5 109-132.3 109zm110-213.7c-29.5 0-53.5-23.9-53.5-53.5s23.9-53.5 53.5-53.5 53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5zM867.2 644.5V453.1h26.5c19.4 0 35.1 15.7 35.1 35.1v121.1c0 19.4-15.7 35.1-35.1 35.1h-26.5zM95.2 609.4V488.2c0-19.4 15.7-35.1 35.1-35.1h26.5v191.3h-26.5c-19.4 0-35.1-15.7-35.1-35.1zM561.5 149.6c0 23.4-15.6 43.3-36.9 49.7v44.9h-30v-44.9c-21.4-6.5-36.9-26.3-36.9-49.7 0-28.6 23.3-51.9 51.9-51.9s51.9 23.3 51.9 51.9z"></path>
        </svg>
        <h2 class="logo-text">Chatbot</h2>
      </div>
      <button id="close-chatbot" class="material-symbols-rounded">keyboard_arrow_down</button>
    </div>

    <!-- Chatbot Body -->
    <div class="chat-body">
      <div class="message bot-message">
        <svg class="bot-avatar" xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 1024 1024">
          <path d="M738.3 287.6H285.7c-59 0-106.8 47.8-106.8 106.8v303.1c0 59 47.8 106.8 106.8 106.8h81.5v111.1c0 .7.8 1.1 1.4.7l166.9-110.6 41.8-.8h117.4l43.6-.4c59 0 106.8-47.8 106.8-106.8V394.5c0-59-47.8-106.9-106.8-106.9zM351.7 448.2c0-29.5 23.9-53.5 53.5-53.5s53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5-53.5-23.9-53.5-53.5zm157.9 267.1c-67.8 0-123.8-47.5-132.3-109h264.6c-8.6 61.5-64.5 109-132.3 109zm110-213.7c-29.5 0-53.5-23.9-53.5-53.5s23.9-53.5 53.5-53.5 53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5zM867.2 644.5V453.1h26.5c19.4 0 35.1 15.7 35.1 35.1v121.1c0 19.4-15.7 35.1-35.1 35.1h-26.5zM95.2 609.4V488.2c0-19.4 15.7-35.1 35.1-35.1h26.5v191.3h-26.5c-19.4 0-35.1-15.7-35.1-35.1zM561.5 149.6c0 23.4-15.6 43.3-36.9 49.7v44.9h-30v-44.9c-21.4-6.5-36.9-26.3-36.9-49.7 0-28.6 23.3-51.9 51.9-51.9s51.9 23.3 51.9 51.9z"></path>
        </svg>
        <div class="message-text">Bonjour ! Je suis ton assistant ODD 🌍<br />Je peux vous aider à en savoir plus sur les objectifs de développement durable et les villes.</div>
      </div>
    </div>

    <!-- Chatbot Footer -->
    <div class="chat-footer">
      <form action="#" class="chat-form">
        <textarea placeholder="Message..." class="message-input" required></textarea>
        <div class="chat-controls">
          <button type="submit" id="send-message" class="material-symbols-rounded">arrow_upward</button>
        </div>
      </form>
    </div>
  </div>

  </div>
  

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const chatBody = document.querySelector(".chat-body");
      const messageInput = document.querySelector(".message-input");
      const sendMessageButton = document.querySelector("#send-message");
      const chatbotToggler = document.querySelector("#chatbot-toggler");
      const closeChatbot = document.querySelector("#close-chatbot");
      
      const initialInputHeight = messageInput.scrollHeight;

      
      const API_BASE_URL = 'http://localhost:3000'; 
      const USER_ID = 'demo_user_' + Math.random().toString(36).substr(2, 9); 

      function createMessageElement(content, className, extraClass = "") {
        const messageDiv = document.createElement("div");
        messageDiv.className = `message ${className} ${extraClass}`.trim();
        messageDiv.innerHTML = content;
        return messageDiv;
      }

      async function generateBotResponse(messageElement, userMessage) {
        try {
          const response = await sendMessageToAPI(userMessage);
          const messageText = messageElement.querySelector(".message-text");
          messageText.innerHTML = response;
          messageElement.classList.remove("thinking");
          chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: "smooth" });
        } catch (error) {
          console.error('Error generating bot response:', error);
          const messageText = messageElement.querySelector(".message-text");
          messageText.innerHTML = "Sorry, I encountered an error. Please try again.";
          messageElement.classList.remove("thinking");
          chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: "smooth" });
        }
      }

      async function sendMessageToAPI(message) {
        try {
          const response = await fetch(`${API_BASE_URL}/api/chat`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              query: message,
              userId: USER_ID,
              codeLanguage: '<?= $language ?>'
            })
          });

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const data = await response.json();
          return data.response;
        } catch (error) {
          console.error('Error sending message to API:', error);
          
          // Fallback responses when server is not available
          const fallbackResponses = {
            "qui êtes-vous" : "Je suis un assistant IA pour la plateforme de développement durable, mais je ne parviens pas à me connecter au serveur principal. Veuillez réessayer plus tard.",
            "qu'est-ce qu'un ODD 1": "L'ODD 1, ou Objectif de Développement Durable numéro 1, vise à éliminer la pauvreté sous toutes ses formes et partout dans le monde.",
            "qu'est-ce qu'un ODD 2": "L'ODD 2 vise à éliminer la faim, à assurer la sécurité alimentaire et une meilleure nutrition, et à promouvoir une agriculture durable. Il est essentiel de garantir que chacun ait accès à une nourriture suffisante, saine et nutritive.",
            "qu'est-ce qu'un ODD 3": "L'ODD 3 est l'objectif de développement durable relatif à la bonne santé et au bien-être. Il vise à assurer une vie saine et à promouvoir le bien-être de tous à tous les âges.",
            "qu'est-ce qu'un ODD 4": "L'ODD 4 vise à assurer une éducation de qualité inclusive et équitable et à promouvoir les possibilités d'apprentissage tout au long de la vie pour tous.",
            "qu'est-ce qu'un ODD 5": "L'ODD 5 est l'Objectif de Développement Durable qui vise à parvenir à l'égalité des sexes et à autonomiser toutes les femmes et les filles.",
            "qu'est-ce qu'un ODD 6": "L'ODD 6 est relatif à l'eau propre et à l'assainissement. Il vise à assurer la disponibilité et la gestion durable de l'eau et de l'assainissement pour tous",
            "qu'est-ce qu'un ODD 7": "L'ODD 7, ou Objectif de Développement Durable 7, vise à garantir l'accès à une énergie abordable, fiable, durable et moderne pour tous.",
            "qu'est-ce qu'un ODD 8": "L'ODD 8 : Travail décent et croissance économique vise à promouvoir une croissance économique soutenue, inclusive et durable, le plein emploi productif et un travail décent pour tous.",
            "qu'est-ce qu'un ODD 9": "L'ODD 9 concerne l'industrie, l'innovation et l'infrastructure : Bâtir une infrastructure résiliente, promouvoir une industrialisation inclusive et durable et encourager l'innovation.",
            "qu'est-ce qu'un ODD 10": "L'ODD 10 vise à réduire les inégalités au sein des pays et entre eux. C'est un objectif crucial pour assurer un développement durable et inclusif pour tous.",
            "qu'est-ce qu'un ODD 11": "L'ODD 11 concerne les villes et communautés durables - rendre les villes inclusives, sûres, résilientes et durables. Cependant, je dois me connecter au serveur pour obtenir des informations détaillées.",
            "qu'est-ce qu'un ODD 12": "L'ODD 12, Consommation et production responsables, vise à garantir des modes de consommation et de production durables.",
            "qu'est-ce qu'un ODD 13": "L'ODD 13, Action climatique, vise à prendre des mesures urgentes pour lutter contre le changement climatique et ses impacts.",
            "qu'est-ce qu'un ODD 14": "L'ODD 14 : Vie aquatique vise à conserver et à utiliser durablement les océans, les mers et les ressources marines aux fins du développement durable.",
            "qu'est-ce qu'un ODD 15": "L'ODD 15, Vie Terrestre, vise à protéger, restaurer et promouvoir l'utilisation durable des écosystèmes terrestres, à gérer durablement les forêts, à lutter contre la désertification, à stopper et inverser la dégradation des sols et à mettre fin à la perte de biodiversité.",
            "qu'est-ce qu'un ODD 16": "L'ODD 16 : Paix, Justice et Institutions Fortes vise à promouvoir des sociétés pacifiques et inclusives pour le développement durable, à assurer l'accès à la justice pour tous et à mettre en place des institutions efficaces, responsables et inclusives à tous les niveaux.",
            "qu'est-ce qu'un ODD 17": "L'ODD 17, Partenariats pour la réalisation des objectifs, vise à renforcer les moyens de mise en œuvre et à revitaliser le partenariat mondial pour le développement durable.",
            "help" : "Je peux vous aider à obtenir des informations sur les ODD, mais je ne parviens pas à me connecter au serveur. Veuillez vérifier si le serveur fonctionne sur localhost:3000."
          };

       
          const lowerMessage = message.toLowerCase();
          for (const [key, response] of Object.entries(fallbackResponses)) {
            if (lowerMessage.includes(key.toLowerCase())) {
              return response;
            }
          }

          return "Je suis désolé, mais je n'arrive pas à me connecter au serveur. Veuillez vous assurer que le serveur de chatbot fonctionne sur localhost:3000 et réessayez.";
        }
      };

      const handleOutgoingMessage = (e) => {
        e.preventDefault();
        
        const userMessage = messageInput.value.trim();
        if (!userMessage) return;
        
        messageInput.value = "";
        messageInput.dispatchEvent(new Event("input"));

        // Add user message
        const outgoingMessageDiv = createMessageElement(`<div class="message-text"></div>`, "user-message");
        outgoingMessageDiv.querySelector(".message-text").textContent = userMessage;
        chatBody.appendChild(outgoingMessageDiv);
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: "smooth" });

        // Add bot thinking message
        setTimeout(() => {
          const messageContent = `<svg class="bot-avatar" xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 1024 1024">
                                  <path d="M738.3 287.6H285.7c-59 0-106.8 47.8-106.8 106.8v303.1c0 59 47.8 106.8 106.8 106.8h81.5v111.1c0 .7.8 1.1 1.4.7l166.9-110.6 41.8-.8h117.4l43.6-.4c59 0 106.8-47.8 106.8-106.8V394.5c0-59-47.8-106.9-106.8-106.9zM351.7 448.2c0-29.5 23.9-53.5 53.5-53.5s53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5-53.5-23.9-53.5-53.5zm157.9 267.1c-67.8 0-123.8-47.5-132.3-109h264.6c-8.6 61.5-64.5 109-132.3 109zm110-213.7c-29.5 0-53.5-23.9-53.5-53.5s23.9-53.5 53.5-53.5 53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5z"></path>
                                  </svg>
                                  <div class="message-text">
                                    <div class="thinking-indicator">
                                      <div class="dot"></div>
                                      <div class="dot"></div>
                                      <div class="dot"></div>
                                    </div>
                                  </div>`;
          
          const incomingMessageDiv = createMessageElement(messageContent, "bot-message", "thinking");
          chatBody.appendChild(incomingMessageDiv);
          chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: "smooth" });
          generateBotResponse(incomingMessageDiv, userMessage);
        }, 600);
      };

      
      messageInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter" && !e.shiftKey && messageInput.value.trim()) {
          e.preventDefault();
          handleOutgoingMessage(e);
        }
      });

      messageInput.addEventListener("input", () => {
        messageInput.style.height = `${initialInputHeight}px`;
        messageInput.style.height = `${messageInput.scrollHeight}px`;
        const chatForm = document.querySelector(".chat-form");
        if (chatForm) {
          chatForm.style.borderRadius = messageInput.scrollHeight > initialInputHeight ? "15px" : "32px";
        }
      });

      sendMessageButton.addEventListener("click", handleOutgoingMessage);
      
      chatbotToggler.addEventListener("click", () => {
        document.body.classList.toggle("show-chatbot");
      });
      
      closeChatbot.addEventListener("click", () => {
        document.body.classList.remove("show-chatbot");
      });

      console.log("Minimal chatbot initialized successfully");
    });
  </script>
</body>
</html>
