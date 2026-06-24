const tokenStore = new Map();

module.exports = {
    getClient: async (clientId, clientSecret) => {
        return { 
            id: clientId, 
            clientId: clientId,
            clientSecret: clientSecret,
            grants: ['password', 'client_credentials', 'refresh_token'], 
            redirectUris: [] 
        };
    },
    getUser: async (username, password) => {
        return { id: 1 }; 
    },
    getUserFromClient: async (client) => {
        return { id: client.id };
    },
    saveToken: async (token, client, user) => {
        const savedToken = {
            accessToken: token.accessToken,
            accessTokenExpiresAt: token.accessTokenExpiresAt,
            refreshToken: token.refreshToken,
            refreshTokenExpiresAt: token.refreshTokenExpiresAt,
            scope: token.scope,
            client: { id: client.id, clientId: client.clientId },
            user: { id: user.id }
        };
        tokenStore.set(savedToken.accessToken, savedToken);
        return savedToken;
    },
    getAccessToken: async (accessToken) => {
        const saved = tokenStore.get(accessToken);
        if (!saved) {
            return null;
        }
        return saved;
    },
    revokeToken: async (token) => {
        const tokenKey = token.accessToken || token; 
        const existed = tokenStore.has(tokenKey);
        tokenStore.delete(tokenKey);
        return existed;
    }
};