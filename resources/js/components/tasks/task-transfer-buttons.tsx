import { DownloadIcon, UploadIcon } from '@/components/icons';

type TaskTransferButtonsProps = {
    onImport: () => void;
    /** URL of the .txt download; a plain link, since the response is a file and not an Inertia page. */
    exportUrl: string;
};

const iconButtonClassName =
    'flex size-9 shrink-0 items-center justify-center rounded-lg text-neutral-500 ring-1 ring-black/10 transition-colors hover:bg-white hover:text-neutral-900 dark:text-neutral-400 dark:ring-white/10 dark:hover:bg-neutral-900 dark:hover:text-white';

/**
 * Pair of outlined icon buttons for importing tasks from a text file and exporting them as .txt.
 */
export default function TaskTransferButtons({
    onImport,
    exportUrl,
}: TaskTransferButtonsProps) {
    return (
        <>
            <button
                type="button"
                onClick={onImport}
                aria-label="Importar tasks"
                title="Importar tasks"
                className={iconButtonClassName}
            >
                <UploadIcon />
            </button>
            <a
                href={exportUrl}
                download
                aria-label="Exportar tasks (.txt)"
                title="Exportar tasks (.txt)"
                className={iconButtonClassName}
            >
                <DownloadIcon />
            </a>
        </>
    );
}
