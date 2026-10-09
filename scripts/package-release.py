"""Package the current deliverable without Git history or local runtime data.

Run from the repository root: py -3 scripts/package-release.py
The ZIP is written next to the repository, so it cannot include itself.
"""

import subprocess
import zipfile
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
DESTINATION = ROOT.parent / "Proyecto_HeyT_entrega.zip"


def main() -> None:
    result = subprocess.run(
        ["git", "ls-files", "--cached", "--others", "--exclude-standard", "-z"],
        cwd=ROOT,
        check=True,
        capture_output=True,
    )
    paths = sorted({Path(raw.decode("utf-8")) for raw in result.stdout.split(b"\0") if raw})
    blocked = {".env", ".env.backup", "database.sqlite", "auth.json"}
    for path in paths:
        if path.name in blocked or any(part in {".git", "vendor", "node_modules", "__pycache__"} for part in path.parts):
            raise RuntimeError(f"Unexpected private/generated file in package list: {path}")

    with zipfile.ZipFile(DESTINATION, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for relative in paths:
            source = ROOT / relative
            if source.is_file():
                archive.write(source, relative.as_posix())

    print(f"Created {DESTINATION} ({len(paths)} files, {DESTINATION.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
