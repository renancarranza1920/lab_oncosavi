#!/usr/bin/env python3
"""Construye un ZIP de producción limpio con una sola carpeta de aplicación."""

import argparse
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import zipfile


PROJECT = Path(__file__).resolve().parents[1]
FOLDER = "app.laboratorioclinicomerino.com"
SOURCE_DIRS = ("app", "bootstrap", "config", "database", "docs", "lang", "public_html", "resources", "routes", "scripts")
SOURCE_FILES = (".env.example", ".htaccess", "artisan", "composer.json", "composer.lock", "package.json", "package-lock.json", "vite.config.js", "README.md", "README_HOSTINGER.md")
RUNTIME_DIRS = (
    "bootstrap/cache", "storage/app/private", "storage/app/public", "storage/framework/cache/data",
    "storage/framework/sessions", "storage/framework/views", "storage/logs", "storage/fonts",
)


def excluded(path: Path) -> bool:
    parts = path.parts
    return (
        any(part in {".git", "node_modules", "__pycache__"} for part in parts)
        or path.name.startswith(".env")
        or path.name in {"hot", ".DS_Store", "auth.json"}
        or path.suffix.lower() in {".sql", ".sqlite", ".sqlite3", ".zip", ".rar", ".pdf", ".log", ".key", ".pem"}
        or parts[:2] in {("bootstrap", "cache"), ("public_html", "storage")}
    )


def copy_sources(destination: Path) -> None:
    for directory in SOURCE_DIRS:
        source = PROJECT / directory
        if not source.is_dir():
            continue
        for current, directories, files in os.walk(source, followlinks=False):
            relative = Path(current).relative_to(PROJECT)
            directories[:] = [name for name in directories if not excluded(relative / name) and not (Path(current) / name).is_symlink()]
            target = destination / relative
            target.mkdir(parents=True, exist_ok=True)
            for filename in files:
                local = Path(current) / filename
                if not excluded(relative / filename) and not local.is_symlink():
                    shutil.copyfile(local, target / filename)
    for filename in SOURCE_FILES:
        source = PROJECT / filename
        if source.is_file() and not source.is_symlink():
            shutil.copyfile(source, destination / filename)
    # Se construyen vacíos: nunca se copia el almacenamiento del laboratorio.
    for directory in RUNTIME_DIRS:
        target = destination / directory
        target.mkdir(parents=True, exist_ok=True)
        (target / ".gitignore").write_text("*\n!.gitignore\n", encoding="utf-8")


def run(command: list[str], cwd: Path, env: dict[str, str] | None = None) -> None:
    subprocess.run(command, cwd=cwd, env=env, check=True)


def remove_vendor_metadata(vendor: Path) -> None:
    # Las instalaciones --prefer-source incluyen repositorios VCS completos.
    for current, directories, files in os.walk(vendor, topdown=True):
        for name in list(directories):
            if name in {".git", ".hg", ".svn"}:
                shutil.rmtree(Path(current) / name)
                directories.remove(name)
        for name in files:
            if (
                name in {".git", "auth.json"} or name.startswith(".env")
                or Path(name).suffix.lower() in {".sql", ".sqlite", ".sqlite3"}
            ):
                (Path(current) / name).unlink()


def package(args: argparse.Namespace) -> None:
    output = args.output.expanduser().resolve()
    if output == PROJECT or PROJECT in output.parents:
        raise ValueError("El ZIP debe guardarse fuera del repositorio de código.")
    for binary in (args.php, args.composer):
        if shutil.which(binary) is None:
            raise ValueError(f"No está disponible {binary}; usa PHP 8.3 y Composer 2.")
    if not args.skip_build:
        if shutil.which("npm") is None:
            raise ValueError("Se necesita Node/npm para construir los recursos antes de empaquetar.")
        if not (PROJECT / "node_modules").is_dir():
            run(["npm", "ci"], PROJECT)
        run(["npm", "run", "build"], PROJECT)
    manifest = PROJECT / "public_html/build/manifest.json"
    if not manifest.is_file():
        raise ValueError("Falta public_html/build/manifest.json; ejecuta npm run build.")
    output.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix="lameri-package-", dir=output.parent) as staging:
        root = Path(staging) / FOLDER
        root.mkdir()
        copy_sources(root)
        # Sin .env ni conexión a una base real durante la construcción.
        environment = dict(os.environ)
        for name in list(environment):
            if name.startswith(("APP_", "DB_", "LAMERI_ADMIN_", "LABORATORIO_", "SESSION_", "CACHE_", "QUEUE_", "MAIL_")):
                del environment[name]
        environment.update({
            "APP_ENV": "production", "APP_DEBUG": "false", "APP_URL": "https://app.laboratorioclinicomerino.com",
            "DB_CONNECTION": "sqlite", "DB_DATABASE": ":memory:", "CACHE_STORE": "array",
            "SESSION_DRIVER": "array", "QUEUE_CONNECTION": "sync", "MAIL_MAILER": "log",
        })
        preference = "--prefer-source" if args.prefer_source else "--prefer-dist"
        run([args.composer, "install", "--no-dev", preference, "--optimize-autoloader", "--no-interaction", "--no-progress", "--no-scripts"], root, environment)
        run([args.php, "artisan", "package:discover", "--ansi"], root, environment)
        run([args.php, "artisan", "filament:upgrade", "--ansi"], root, environment)
        remove_vendor_metadata(root / "vendor")
        # Los proveedores generan cachés reutilizables; las vistas y logs vuelven vacíos.
        for directory in ("storage/framework/views", "storage/framework/cache/data", "storage/framework/sessions", "storage/logs"):
            for entry in (root / directory).iterdir():
                if entry.name != ".gitignore":
                    shutil.rmtree(entry) if entry.is_dir() else entry.unlink()
        for path in root.rglob("*"):
            if not path.is_symlink():
                path.chmod(0o755 if path.is_dir() else 0o644)
        temporary_zip = output.with_suffix(".building.zip")
        try:
            with zipfile.ZipFile(temporary_zip, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
                archive.write(root, FOLDER + "/")
                for path in sorted(root.rglob("*")):
                    if not path.is_symlink():
                        archive.write(path, path.relative_to(Path(staging)).as_posix())
            temporary_zip.replace(output)
        finally:
            temporary_zip.unlink(missing_ok=True)
    print(f"ZIP listo: {output} ({output.stat().st_size / 1024 / 1024:.1f} MiB)")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=PROJECT.parent / "output/lameri_hostinger.zip")
    parser.add_argument("--skip-build", action="store_true", help="Usar los recursos ya compilados en public_html/build.")
    parser.add_argument("--prefer-source", action="store_true", help="Instalar las dependencias desde Git/caché VCS cuando los ZIP de GitHub no están disponibles.")
    parser.add_argument("--php", default="php")
    parser.add_argument("--composer", default="composer")
    args = parser.parse_args()
    try:
        package(args)
    except (ValueError, subprocess.CalledProcessError) as error:
        parser.exit(1, f"No se generó el ZIP: {error}\n")


if __name__ == "__main__":
    main()
