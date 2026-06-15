FROM node:20-alpine

WORKDIR /app

COPY package*.json ./
RUN apk add --no-cache curl
RUN npm ci --only=production

COPY . .

EXPOSE 3000

HEALTHCHECK CMD curl -f http://localhost:3000/health || exit 1

CMD ["node", "src/index.js"]
