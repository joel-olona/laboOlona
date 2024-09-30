const dotenv = require('dotenv');
dotenv.config();
const OpenAI = require('openai');
const apiKey = process.env.OPENAI_API_KEY || process.argv[3];
const openai = new OpenAI({
    apiKey
});
const assistantId = 'asst_mMbBh4WpxPbW4PEJNoi3Lwcp';

// Fonction pour vérifier le statut du run
const checkRunStatus = async (threadId, runId) => {
    try {
        const runStatus = await openai.beta.threads.runs.retrieve(threadId, runId);
        return runStatus;
    } catch (error) {
        console.error('Error checking run status:', error);
    }
};

async function getChatCompletion(text) {
    try {
        // Crée un nouveau thread
        const thread = await openai.beta.threads.create();
        const threadId = thread.id;
        await openai.beta.threads.messages.create(threadId, {
            role: "user",
            content: "This is a candidate report for recruitment:" + text
            ,
        });

        // Crée et exécute un run
        const run = await openai.beta.threads.runs.create(threadId, {
            assistant_id: assistantId
        });

        // Vérifie périodiquement le statut du run
        let runStatus;
        do {
            await new Promise(resolve => setTimeout(resolve, 5000)); // Attendre 5 secondes avant de vérifier le statut
            runStatus = await checkRunStatus(threadId, run.id);
            //   console.log('Current run status:', runStatus.status);
        } while (runStatus.status !== 'completed');

        // Liste les messages du thread après la complétion du run
        if (runStatus.status === 'completed') {
            const messages = await openai.beta.threads.messages.list(threadId);
            for (const message of messages.data.reverse()) {
                // console.log(`${message.role} > ${message.content[0].text.value}`);
                if (message.role === 'assistant') {
                    let jsonResult = message.content[0].text.value;
                    console.log(jsonResult);
                }
            }
        } else {
            console.log(runStatus.status);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

const text = process.argv[2]; // Récupère l'argument depuis la ligne de commande
getChatCompletion(text);
