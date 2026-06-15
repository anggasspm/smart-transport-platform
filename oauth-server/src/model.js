module.exports = {
    getClient: async (clientId, clientSecret) => {
        return { id: clientId, grants: ['password', 'client_credentials', 'refresh_token'], redirectUris: [] };
    },
    getUser: async (username, password) => {
        return { id: 1 };
    },
    saveToken: async (token, client, user) => {
        return token;
    },
    getAccessToken: async (accessToken) => {
        return { accessToken };
    }
};