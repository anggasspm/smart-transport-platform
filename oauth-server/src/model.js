const pool = require('./db');
const bcrypt = require('bcrypt');

module.exports = {

    async getClient(clientId, clientSecret) {

        const [rows] = await pool.execute(
            `
            SELECT *
            FROM oauth_clients
            WHERE client_id = ?
            AND client_secret = ?
            LIMIT 1
            `,
            [clientId, clientSecret]
        );

        if (rows.length === 0)
            return null;

        const client = rows[0];

        return {
            id: client.client_id,
            clientId: client.client_id,
            clientSecret: client.client_secret,
            grants: client.grant_types
                .split(',')
                .map(g => g.trim()),
            redirectUris: client.redirect_uri
                ? [client.redirect_uri]
                : []
        };
    },

    async getUser(username, password) {

        const [rows] = await pool.execute(
            `
            SELECT id,email,password
            FROM passenger_passengers
            WHERE email = ?
            LIMIT 1
            `,
            [username]
        );

        if (rows.length === 0)
            return null;

        const passenger = rows[0];

        const ok = await bcrypt.compare(password, passenger.password);

        if (!ok) {
            return null;
        }

        return {
            id: passenger.id
        };
    },

    async getUserFromClient(client) {
        return {
            id: null
        };
    },

    async saveToken(token, client, user) {

        await pool.execute(
            `
            INSERT INTO oauth_tokens
            (
                client_id,
                user_id,
                access_token,
                access_token_expires_at,
                refresh_token,
                refresh_token_expires_at
            )
            VALUES (?,?,?,?,?,?)
            `,
            [
                client.id,
                user?.id ?? null,
                token.accessToken,
                token.accessTokenExpiresAt,
                token.refreshToken ?? null,
                token.refreshTokenExpiresAt ?? null
            ]
        );

        return {
            accessToken: token.accessToken,
            accessTokenExpiresAt: token.accessTokenExpiresAt,
            refreshToken: token.refreshToken,
            refreshTokenExpiresAt: token.refreshTokenExpiresAt,
            scope: token.scope,
            client: {
                id: client.id,
                clientId: client.clientId
            },
            user: user
                ? { id: user.id }
                : null
        };
    },

    async getAccessToken(accessToken) {

        const [rows] = await pool.execute(
            `
            SELECT *
            FROM oauth_tokens
            WHERE access_token = ?
            LIMIT 1
            `,
            [accessToken]
        );

        if (rows.length === 0)
            return null;

        const token = rows[0];

        if(new Date(token.access_token_expires_at)<new Date())
            return null;

        return {
            accessToken: token.access_token,
            accessTokenExpiresAt: token.access_token_expires_at,
            refreshToken: token.refresh_token,
            refreshTokenExpiresAt: token.refresh_token_expires_at,
            client: {
                id: token.client_id,
                clientId: token.client_id
            },
            user: token.user_id
                ? { id: token.user_id }
                : null
        };
    },

    async getRefreshToken(refreshToken){

        const [rows] = await pool.execute(
        `
        SELECT
            t.*,
            c.client_secret,
            c.grant_types,
            c.redirect_uri
        FROM oauth_tokens t
        JOIN oauth_clients c
            ON t.client_id = c.client_id
        WHERE t.refresh_token = ?
        LIMIT 1
        `,
        [refreshToken]
        );

        if(rows.length===0)
            return null;

        const t=rows[0];

        if (new Date(t.refresh_token_expires_at) < new Date())
            return null;

        return {
            refreshToken: t.refresh_token,
            refreshTokenExpiresAt: t.refresh_token_expires_at,

            client: {
                id: t.client_id,
                clientId: t.client_id,
                clientSecret: t.client_secret,
                grants: t.grant_types.split(',').map(x => x.trim()),
                redirectUris: t.redirect_uri
                    ? [t.redirect_uri]
                    : []
            },

            user: t.user_id
                ? { id: t.user_id }
                : null
        };
    },

    async revokeToken(token) {

        const value = token.refreshToken || token.accessToken;

        const column = token.refreshToken
            ? "refresh_token"
            : "access_token";

        const [result] = await pool.execute(
            `DELETE FROM oauth_tokens WHERE ${column} = ?`,
            [value]
        );

        return result.affectedRows > 0;
    }

};