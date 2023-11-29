const dotenv = require('dotenv');
dotenv.config();
const OpenAI = require('openai');

const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

async function getChatCompletion(text) {
    try {
        const response = await openai.chat.completions.create({
            model: "gpt-4",
            messages: [{ role: 'user', content: 'Translate this text from en to fr: ' + text }],
            temperature: 0,
            max_tokens: 2096,
        });

        console.log(response.choices[0].message.content);
        // Vous pouvez traiter la réponse ici
    } catch (error) {
        console.error('Error:', error);
    }
}

const text = process.argv[2]; // Récupère l'argument depuis la ligne de commande
getChatCompletion(text);
