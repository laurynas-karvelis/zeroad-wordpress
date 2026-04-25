#!/usr/bin/env bun

const { version } = await Bun.file("package.json").json()

const replace = (content, pattern, replacement) => content.replace(pattern, replacement)

const syncFile = async (path, ...replacements) => {
  let content = await Bun.file(path).text()
  for (const [pattern, replacement] of replacements) {
    content = replace(content, pattern, replacement)
  }
  await Bun.write(path, content)
}

await syncFile("plugins/zero-ad-network/readme.txt", [/^Stable tag:.*/m, `Stable tag: ${version}`])

await syncFile(
  "plugins/zero-ad-network/zero-ad-network.php",
  [/^([ \t]*\*[ \t]*Version:[ \t]+)\S+$/m, `$1${version}`],
  [/(define\(["']ZEROAD_VERSION["'],\s*["'])[\d.]+["']\)/, `$1${version}")`]
)

console.log(`Synced version ${version}`)
