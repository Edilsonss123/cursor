import { randomUUID } from 'node:crypto';
import { loadUsuarios, saveUsuarios } from './store.js';

function nowIso() {
    return new Date().toISOString();
}

function isPlainUsuarioRecord(value) {
    return (
        value !== null &&
        typeof value === 'object' &&
        typeof value.id === 'string' &&
        typeof value.nome === 'string' &&
        typeof value.email === 'string' &&
        typeof value.criadoEm === 'string' &&
        typeof value.atualizadoEm === 'string'
    );
}

function normalizeList(raw) {
    return raw.filter(isPlainUsuarioRecord);
}

/**
 * @param {{ nome: string, email: string }} input
 */
export function criarUsuario(input) {
    const lista = normalizeList(loadUsuarios());
    const id = randomUUID();
    const ts = nowIso();
    const usuario = {
        id,
        nome: input.nome.trim(),
        email: input.email.trim(),
        criadoEm: ts,
        atualizadoEm: ts
    };
    lista.push(usuario);
    saveUsuarios(lista);
    return usuario;
}

export function listarUsuarios() {
    return normalizeList(loadUsuarios());
}

/**
 * @param {{ id: string }} input
 */
export function removerUsuario(input) {
    const lista = normalizeList(loadUsuarios());
    const next = lista.filter((u) => u.id !== input.id);
    if (next.length === lista.length) {
        return { ok: false, motivo: 'Usuário não encontrado.' };
    }
    saveUsuarios(next);
    return { ok: true };
}

/**
 * @param {{ id: string, nome?: string, email?: string }} input
 */
export function atualizarUsuario(input) {
    const nome = input.nome?.trim();
    const email = input.email?.trim();
    if (!nome && !email) {
        return { ok: false, motivo: 'Informe nome ou e-mail para atualizar.' };
    }
    const lista = normalizeList(loadUsuarios());
    const idx = lista.findIndex((u) => u.id === input.id);
    if (idx === -1) {
        return { ok: false, motivo: 'Usuário não encontrado.' };
    }
    const atual = lista[idx];
    const atualizado = {
        ...atual,
        nome: nome && nome.length > 0 ? nome : atual.nome,
        email: email && email.length > 0 ? email : atual.email,
        atualizadoEm: nowIso()
    };
    lista[idx] = atualizado;
    saveUsuarios(lista);
    return { ok: true, usuario: atualizado };
}
