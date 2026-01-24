# Workflow Decision Tree

```
GitHub Event
     |
     v
┌────────────────────────┐
│  What triggered this?  │
└────────────────────────┘
            |
            v
    ┌───────┴───────┐
    |               |
    v               v
[TAG PUSH]     [BRANCH PUSH]     [MANUAL]
    |               |                |
    v               v                v
┌─────────┐   ┌─────────┐    ┌─────────────┐
│ Tag ref │   │ main    │    │ Dispatch    │
└─────────┘   └─────────┘    └─────────────┘
    |               |                |
    v               v                v
Multi-arch      AMD64 only      Multi-arch
(amd64+arm64)   (faster)        (amd64+arm64)
    |               |                |
    v               v                v
Tags:           Tags:            Tags:
- latest        - dev            - {input}
- {tag-name}
```

## Examples:

### Scenario 1: Creating a release
```bash
git tag v1.0.0
git push origin v1.0.0
```
**Result:**
- ✅ Multi-architecture build (AMD64 + ARM64)
- ✅ Tagged as: `latest` and `v1.0.0`
- ✅ Available on both Docker Hub and GHCR

### Scenario 2: Regular development push
```bash
git push origin main
```
**Result:**
- ✅ AMD64-only build (faster)
- ✅ Tagged as: `dev`
- ✅ Used by `docker-compose-dev.yaml`

### Scenario 3: Manual workflow run
```bash
# Via GitHub Actions UI
# Input tag: "testing"
```
**Result:**
- ✅ Multi-architecture build (AMD64 + ARM64)
- ✅ Tagged as: `testing`
