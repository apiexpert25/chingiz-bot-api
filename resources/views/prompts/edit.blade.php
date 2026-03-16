<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prompt</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-color: #f8fafc;
            --accent-color: #3b82f6;
            --accent-hover: #2563eb;
            --border-color: #334155;
            --success-color: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
            background: linear-gradient(to right, #60a5fa, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        label {
            font-size: 0.875rem;
            font-weight: 400;
            color: #94a3b8;
        }

        textarea {
            width: 100%;
            min-height: 150px;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            color: var(--text-color);
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            line-height: 1.6;
            resize: none;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        textarea:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        button:hover {
            background-color: var(--accent-hover);
        }

        button:active {
            transform: scale(0.98);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .logout-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .logout-link a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .logout-link a:hover {
            color: var(--text-color);
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Edit Prompt</h1>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('prompts.update') }}" method="POST">
            @csrf
            <div class="input-group">
                <label for="content">Prompt Content</label>
                <textarea id="content" name="content"
                    oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                    placeholder="Enter your prompt here...">{{ $prompt->getContent() }}</textarea>
            </div>

            <button type="submit">
                <span>Save Prompt</span>
            </button>
        </form>

        <div class="logout-link">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                    style="background: none; border: none; color: #94a3b8; font-size: 0.875rem; cursor: pointer; padding: 0; width: auto;">Logout</button>
            </form>
        </div>
    </div>

    <script>
        // Auto-resize on load
        window.addEventListener('load', () => {
            const textarea = document.getElementById('content');
            textarea.style.height = textarea.scrollHeight + 'px';
        });
    </script>
</body>

</html>