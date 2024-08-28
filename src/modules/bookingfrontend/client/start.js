const { exec } = require('child_process');

const nextEnv = process.env.NEXT_ENV || 'development';

const command = nextEnv === 'production' ? 'npm start' : 'npm run dev';

exec(command, (error, stdout, stderr) => {
    if (error) {
        console.error(`exec error: ${error}`);
        return;
    }
    console.log(`stdout: ${stdout}`);
    console.error(`stderr: ${stderr}`);
});