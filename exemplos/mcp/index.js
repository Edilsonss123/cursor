#!/usr/bin/env node
/**
 * MCP de usuários com persistência em JSON.
 * Cursor: adicione em configurações MCP (stdio):
 *   command: node
 *   args: ["<caminho-absoluto>/mcp-usuarios/index.js"]
 * Opcional: env MCP_USUARIOS_DATA_FILE = caminho do arquivo JSON.
 */
import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import * as z from 'zod/v4';
import {
    atualizarUsuario,
    criarUsuario,
    listarUsuarios,
    removerUsuario
} from './lib/usuarios-service.js';

function jsonText(value) {
    return JSON.stringify(value, null, 2);
}

function toolError(message) {
    return {
        content: [{ type: 'text', text: message }],
        isError: true
    };
}

const mcpServer = new McpServer(
    {
        name: 'mcp-usuarios',
        version: '1.0.0'
    },
    {
        instructions:
            'Ferramentas para CRUD de usuários persistido em arquivo JSON. Use listar_usuarios para consultar; criar_usuario precisa de nome e email; atualizar_usuario precisa do id e pelo menos nome ou email; remover_usuario precisa do id.'
    }
);

mcpServer.registerTool(
    'criar_usuario',
    {
        description: 'Cria um usuário com nome e email e grava no arquivo de dados.',
        inputSchema: {
            nome: z.string().min(1).describe('Nome exibido do usuário'),
            email: z.email().describe('Email válido do usuário')
        }
    },
    async (input) => {
        const usuario = criarUsuario(input);
        return { content: [{ type: 'text', text: jsonText({ sucesso: true, usuario }) }] };
    }
);

mcpServer.registerTool(
    'listar_usuarios',
    {
        description: 'Lista todos os usuários armazenados no arquivo.',
        inputSchema: {}
    },
    async () => {
        const usuarios = listarUsuarios();
        return {
            content: [{ type: 'text', text: jsonText({ total: usuarios.length, usuarios }) }]
        };
    }
);

mcpServer.registerTool(
    'remover_usuario',
    {
        description: 'Remove o usuário com o id informado e atualiza o arquivo.',
        inputSchema: {
            id: z.uuid().describe('Identificador UUID do usuário')
        }
    },
    async ({ id }) => {
        const resultado = removerUsuario({ id });
        if (!resultado.ok) {
            return toolError(resultado.motivo);
        }
        return { content: [{ type: 'text', text: jsonText({ sucesso: true, removidoId: id }) }] };
    }
);

mcpServer.registerTool(
    'atualizar_usuario',
    {
        description: 'Atualiza nome e/ou email do usuário pelo id e persiste no arquivo.',
        inputSchema: {
            id: z.uuid().describe('Identificador UUID do usuário'),
            nome: z.string().min(1).optional().describe('Novo nome (opcional)'),
            email: z.email().optional().describe('Novo email (opcional)')
        }
    },
    async (input) => {
        const resultado = atualizarUsuario(input);
        if (!resultado.ok) {
            return toolError(resultado.motivo);
        }
        return {
            content: [{ type: 'text', text: jsonText({ sucesso: true, usuario: resultado.usuario }) }]
        };
    }
);

async function main() {
    const transport = new StdioServerTransport();
    await mcpServer.connect(transport);
}

main().catch((error) => {
    console.error('[mcp-usuarios]', error);
    process.exit(1);
});
