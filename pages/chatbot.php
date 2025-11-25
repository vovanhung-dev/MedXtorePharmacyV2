<!DOCTYPE html>
<html>
<head>
    <title>Chatbot Y Tế</title>
    <style>
        /* CSS cho chatbot */
        #chatbox-container {
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            background-color: #fff;
            border: 1px solid #ccc;
            position: fixed;
            bottom: 70px;
            right: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 10px;
            display: none;  /* Chatbot ẩn theo mặc định */
        }

        #chat-header {
            font-weight: bold;
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: center;
        }

        #chat-box {
            height: 300px;
            overflow-y: auto;
            padding: 10px;
            border-bottom: 1px solid #ccc;
            margin-bottom: 10px;
        }

        #chat-form {
            display: flex;  /* Sử dụng flexbox */
            gap: 10px;  /* Khoảng cách giữa input và button */
        }

        #chat-form input {
            width: 80%;
            padding: 10px;
            box-sizing: border-box;  /* Đảm bảo padding không ảnh hưởng đến tổng chiều rộng */
        }

        #chat-form button {
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 5px;
        }

        #chat-form button:hover {
            background-color: #0056b3;  /* Màu khi hover */
        }

        /* CSS cho nút kích hoạt chatbot */
        #chat-toggle-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
        }

        #chat-toggle-btn:hover {
            background-color: #0056b3;  /* Màu khi hover */
        }
    </style>
</head>
<body>
    <button id="chat-toggle-btn">💬</button>

    <div id="chatbox-container"> <!-- Chatbot ẩn theo mặc định -->
        <div id="chat-header">Chat với Dược sĩ ảo</div>
        <div id="chat-box"></div>
        <form id="chat-form">
            <input type="text" id="user-input" placeholder="Hỏi về thuốc, triệu chứng..." required />
            <button type="submit">Gửi</button>
        </form>
    </div>

    <script>
        const form = document.getElementById('chat-form');
        const chatBox = document.getElementById('chat-box');
        const input = document.getElementById('user-input');
        const chatContainer = document.getElementById('chatbox-container');
        const toggleBtn = document.getElementById('chat-toggle-btn');

        // Toggle chatbot visibility
        toggleBtn.addEventListener('click', () => {
            // Nếu chatbot đang ẩn, hiển thị nó, nếu đang hiển thị thì ẩn
            if (chatContainer.style.display === 'none' || chatContainer.style.display === '') {
                chatContainer.style.display = 'block';  // Hiển thị chatbot
            } else {
                chatContainer.style.display = 'none';  // Ẩn chatbot
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = input.value;
            chatBox.innerHTML += `<div class="chat-message user"><strong>Bạn:</strong> ${msg}</div>`;
            input.value = '';

            const res = await fetch('http://localhost/controllers/ChatbotController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msg })
            });
            const data = await res.json();
            chatBox.innerHTML += `<div class="chat-message bot"><strong>Bot:</strong> ${data.reply}</div>`;
        });
    </script>
</body>
</html>
