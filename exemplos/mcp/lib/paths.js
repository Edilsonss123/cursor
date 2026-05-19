import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));

/**
 * Caminho do arquivo JSON de usuários.
 * Defina MCP_USUARIOS_DATA_FILE para usar outro arquivo.
 */
export function resolveDataFilePath() {
    const override = process.env.MCP_USUARIOS_DATA_FILE;
    if (override && override.trim().length > 0) {
        return override.trim();
    }
    return join(__dirname, '..', 'data', 'usuarios.json');
}
