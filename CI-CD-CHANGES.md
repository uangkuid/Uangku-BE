# CI/CD Multi-Architecture Configuration Changes

## Summary
This document describes the changes made to the CI/CD pipeline to support multi-architecture builds only for tagged releases, while using AMD64-only builds for development pushes.

## Changes Made

### 1. GitHub Actions Workflow (`.github/workflows/docker-image.yml`)

#### Trigger Configuration
- **Added**: Tag push trigger to support building on git tags
  ```yaml
  on:
    push:
      branches:
        - main
      tags:
        - '*'
  ```

#### Build Strategy

The workflow now uses different configurations based on the trigger type:

**For Tag Pushes:**
- Platforms: `linux/amd64,linux/arm64` (multi-architecture)
- Docker tags: 
  - `latest`
  - `{tag-name}` (the actual git tag)
- Example: Creating tag `v1.0.0` will build and push:
  - `oratakashi/uangku-be:latest`
  - `oratakashi/uangku-be:v1.0.0`
  - `ghcr.io/uangkuid/uangku-be:latest`
  - `ghcr.io/uangkuid/uangku-be:v1.0.0`

**For Branch Pushes (main):**
- Platforms: `linux/amd64` (AMD64 only, faster builds)
- Docker tags:
  - `dev`
  - `dev-{short-sha}` (7 characters)
- Example: Pushing to main will build and push:
  - `oratakashi/uangku-be:dev`
  - `oratakashi/uangku-be:dev-abc1234`
  - `ghcr.io/uangkuid/uangku-be:dev`
  - `ghcr.io/uangkuid/uangku-be:dev-abc1234`

**For Manual Workflow Dispatch:**
- Platforms: `linux/amd64,linux/arm64` (multi-architecture)
- Docker tags: Based on input parameter (default: `latest`)

### 2. Docker Compose Configuration

#### `docker-compose-dev.yaml`
- **Changed**: Image tag from `latest` to `dev`
- **Added**: Platform specification to force AMD64 architecture (since `dev` images are AMD64-only)
- This ensures the development environment uses the latest development build
  ```yaml
  api:
    image: oratakashi/uangku-be:dev
    platform: linux/amd64
  ```

#### `docker-compose.yaml` (Production)
- **No changes**: Continues to use `latest` tag for production deployments

## Usage

### For Development Workflow
1. Push to `main` branch → Builds AMD64-only image with `dev` tag
2. Use `docker-compose-dev.yaml` to run the latest dev build:
   ```bash
   docker-compose -f docker-compose-dev.yaml up
   ```

### For Production Release
1. Create a git tag (e.g., `v1.0.0`):
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```
2. GitHub Actions will build multi-architecture images (AMD64 + ARM64)
3. Images will be tagged as `latest` and `v1.0.0`
4. Use `docker-compose.yaml` to run production:
   ```bash
   docker-compose up
   ```

## Benefits

1. **Faster Development Builds**: AMD64-only builds are faster for development iterations
2. **Multi-Architecture for Production**: ARM64 support for production deployments (e.g., AWS Graviton, Apple Silicon)
3. **Clear Separation**: `dev` tag for development, `latest` tag for production
4. **Version Tracking**: Git tags are preserved as Docker image tags for easier version management
