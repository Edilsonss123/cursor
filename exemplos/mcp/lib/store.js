import { mkdirSync, readFileSync, renameSync, writeFileSync, existsSync } from 'node:fs';
import { dirname } from 'node:path';
import { resolveDataFilePath } from './paths.js';

function ensureParentDir(filePath) {
    const dir = dirname(filePath);
    mkdirSync(dir, { recursive: true });
}

/**
 * Lê a lista de usuários do disco. Retorna array vazio se o arquivo não existir ou estiver vazio.
 */
export function loadUsuarios() {
    const filePath = resolveDataFilePath();
    if (!existsSync(filePath)) {
        return [];
    }
    const raw = readFileSync(filePath, 'utf8').trim();
    if (raw.length === 0) {
        return [];
    }
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) {
        throw new Error('Arquivo de dados inválido: esperado um array JSON.');
    }
    return parsed;
}

/**
 * Grava a lista completa no disco (escrita atômica via arquivo temporário).
 */
export function saveUsuarios(usuarios) {
    const filePath = resolveDataFilePath();
    ensureParentDir(filePath);
    const tmpPath = `${filePath}.${process.pid}.tmp`;
    const json = `${JSON.stringify(usuarios, null, 2)}\n`;
    writeFileSync(tmpPath, json, 'utf8');
    renameSync(tmpPath, filePath);
}
