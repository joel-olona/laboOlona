const dotenv = require('dotenv');
dotenv.config();
const OpenAI = require('openai');
const apiKey = process.env.OPENAI_API_KEY || process.argv[3];
const openai = new OpenAI({
  apiKey
});

async function getChatCompletion(text) {
    try {
        const response = await openai.chat.completions.create({
            model: "gpt-4",
            messages: [{ role: 'user', content: 'Translate this text from fr to en: ' + text }],
            temperature: 0,
            max_tokens: 2096,
        });

        console.log(response.choices[0].message.content);
        return response.choices[0].message.content;
        // Vous pouvez traiter la réponse ici
    } catch (error) {
        console.error('Error:', error);
    }
}

const text = process.argv[2]; // Récupère l'argument depuis la ligne de commande
getChatCompletion(text);
