FROM node:20-bullseye

# Install a system chromium and required libs for headless chrome
RUN apt-get update && apt-get install -y \
    chromium \
    ca-certificates \
    fonts-liberation \
    libnss3 \
    libx11-6 \
    libx11-xcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxrandr2 \
    libasound2 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libgbm1 \
    libgtk-3-0 \
    libxkbcommon0 \
    --no-install-recommends && rm -rf /var/lib/apt/lists/*

# Tell puppeteer not to download Chromium (we use system chromium)
ENV PUPPETEER_SKIP_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

WORKDIR /usr/src/app

# Copy only package files first to leverage Docker cache
COPY package.json package-lock.json* ./

# Install dependencies
RUN npm ci --omit=dev

# Copy project files (needed for tools/visual-check.js)
COPY . .

# Default command: run the visual-check script (connects to the `site` service)
CMD ["node", "tools/visual-check.js"]

