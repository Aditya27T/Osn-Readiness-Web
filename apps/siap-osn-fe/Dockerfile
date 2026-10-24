FROM oven/bun:1-slim

WORKDIR /app

# Salin file dependensi
COPY package.json bun.lock ./

# Install seluruh dependensi
RUN bun install

# Salin seluruh file aplikasi
COPY . .

EXPOSE 5173

CMD ["bunx", "--bun", "vite", "--host", "0.0.0.0", "--port", "5173"]
