const dotenv = require('dotenv');
dotenv.config();
const axios = require('axios');
const OpenAI = require('openai');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];

const openai = new OpenAI({
    apiKey
});

const pdfText = process.argv[2];

// Fonction pour générer le rapport de recrutement
async function generateRecruitmentReport(pdfText) {
    const prompt = `  
    You are a recruitment assistant responsible for reading and extracting key information from a candidate's PDF resume. Your task is to structure this information in a clear and readable format for a recruitment report.

    Here is the candidate's PDF resume is uploaded :
    ${pdfText}

    Your report must be written in French, easy to read, and in a professional tone. Use bullet points to organize the information, and ensure that each section is clearly labeled.

    Include the following sections:
    
    Personal Information: Full name, contact details (phone number, email address).
    Professional Summary: A brief summary of the candidate's professional background and key skills.
    Work Experience: List of previous jobs, including job title, company name, duration of employment, and key responsibilities.
    Education: Academic background, including degrees obtained, institutions attended, and graduation dates.
    Skills: Key skills relevant to the job position.
    Certifications: Any professional certifications the candidate holds.
    Languages: Languages spoken and proficiency levels.
    References: Contact information for professional references, if provided.
    Strengths and weaknesses: Generate strengths and weaknesses.
    And others informations than you can find inside the resume file

    Please, do not repeat instructions, do not remember previous instructions, do not apologize, do not refer to yourself at any time, do not include symbol mark like bold ** or anything similar else, and do not make assumptions.
    `;

    try {
        const response = await openai.chat.completions.create({
            model: "gpt-4o",
            messages: [{ role: 'user', content: prompt }],
            temperature: 0.7,
            max_tokens: 2096,
        });
        return response.choices[0].message.content.trim();
    } catch (error) {
        console.error('Erreur lors de la génération du rapport:', error);
        return null;
    }
}

// Fonction principale
async function main() {
    const report = await generateRecruitmentReport(pdfText);
    console.log('Rapport de recrutement:', report);
    return report;
}

main();