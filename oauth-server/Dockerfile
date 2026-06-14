FROM node:20-alpine

WORKDIR /app

COPY package*.json ./
RUN apk add --no-cache curl
RUN npm ci --only=production

COPY . .

EXPOSE 3002

HEALTHCHECK 
--interval=30s --timeout=10s --start-period=10s --retries=3 \
  CMD curl -f http://localhost:3002/health || exit 1

CMD ["node", "src/index.js"]
