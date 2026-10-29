import { cn } from '@/lib/utils';

type EmptyTasksStateProps = {
    onCreateForToday: () => void;
    onScheduleForAnotherDay: () => void;
};

/**
 * Shown when nothing is due today: a gray panel framed by fading dashed lines, with the two ways to add a task.
 */
export default function EmptyTasksState({
    onCreateForToday,
    onScheduleForAnotherDay,
}: EmptyTasksStateProps) {
    return (
        <section className="relative flex w-full flex-1 items-center justify-center py-10">
            <div className="relative mx-auto flex min-h-[26rem] w-full bg-gray-100 dark:bg-neutral-900">
                <DashedLine side="top" />
                <DashedLine side="bottom" />
                <DashedLine side="left" />
                <DashedLine side="right" />

                <div className="flex w-full flex-col items-center justify-center p-4">
                    <h2 className="text-center text-lg font-medium text-neutral-600 md:text-2xl dark:text-neutral-400">
                        Vamos planejar{' '}
                        <span className="text-black text-shadow-black/5 text-shadow-sm dark:text-white">
                            o seu dia
                        </span>
                        .
                    </h2>
                    <p className="mx-auto mt-4 max-w-lg text-center text-sm text-balance text-neutral-600 md:text-base dark:text-neutral-400">
                        Você ainda não tem tasks para entregar hoje. Crie a
                        primeira agora ou agende uma para outro dia.
                    </p>
                    <div className="mt-4 flex w-full flex-col items-center justify-center gap-4 sm:w-auto sm:flex-row">
                        <button
                            type="button"
                            onClick={onCreateForToday}
                            className="flex w-full cursor-pointer items-center justify-center rounded-lg bg-blue-500 px-4 py-2 font-medium text-white shadow-[0px_0px_10px_0px_rgba(30,64,175,0.2)_inset] ring ring-white/20 ring-offset-2 ring-offset-blue-500 transition-all duration-200 ring-inset hover:shadow-[0px_0px_20px_0px_rgba(30,64,175,0.4)_inset] hover:ring-white/40 active:scale-98 sm:w-auto dark:shadow-[0px_0px_10px_0px_rgba(30,64,175,0.2)_inset] dark:hover:shadow-[0px_0px_20px_0px_rgba(30,64,175,0.3)_inset]"
                        >
                            Criar task para hoje
                        </button>
                        <button
                            type="button"
                            onClick={onScheduleForAnotherDay}
                            className="flex w-full cursor-pointer items-center justify-center rounded-lg border border-transparent bg-transparent px-4 py-2 font-medium text-blue-500 transition-all duration-200 hover:border-blue-500 hover:bg-blue-500 hover:text-white active:scale-98 sm:w-auto dark:text-blue-400 dark:hover:border-blue-400 dark:hover:bg-blue-400 dark:hover:text-white"
                        >
                            Agendar para outro dia
                        </button>
                    </div>
                </div>
            </div>
        </section>
    );
}

const dashedLineBase =
    '[--background:#ffffff] [--fade-stop:90%] [--color-dark:var(--color-neutral-700)] [--color:var(--color-neutral-500)] absolute mask-exclude z-30';

const horizontalLine =
    '[--height:1px] [--offset:200px] [--width:5px] left-[calc(var(--offset)/2*-1)] h-(--height) w-[calc(100%+var(--offset))] bg-[linear-gradient(to_right,var(--color),var(--color)_50%,transparent_0,transparent)] bg-size-[var(--width)_var(--height)] mask-[linear-gradient(to_left,var(--background)_var(--fade-stop),transparent),linear-gradient(to_right,var(--background)_var(--fade-stop),transparent),linear-gradient(black,black)] dark:bg-[linear-gradient(to_right,var(--color-dark),var(--color-dark)_50%,transparent_0,transparent)]';

const verticalLine =
    '[--height:5px] [--offset:150px] [--width:1px] top-[calc(var(--offset)/2*-1)] h-[calc(100%+var(--offset))] w-(--width) bg-[linear-gradient(to_bottom,var(--color),var(--color)_50%,transparent_0,transparent)] bg-size-[var(--width)_var(--height)] mask-[linear-gradient(to_top,var(--background)_var(--fade-stop),transparent),linear-gradient(to_bottom,var(--background)_var(--fade-stop),transparent),linear-gradient(black,black)] dark:bg-[linear-gradient(to_bottom,var(--color-dark),var(--color-dark)_50%,transparent_0,transparent)]';

const sideClasses = {
    top: cn(horizontalLine, 'top-0 bottom-auto'),
    bottom: cn(horizontalLine, 'top-auto bottom-0'),
    left: cn(verticalLine, 'right-auto left-0'),
    right: cn(verticalLine, 'right-0 left-auto'),
};

function DashedLine({ side }: { side: keyof typeof sideClasses }) {
    return <div className={cn(dashedLineBase, sideClasses[side])} />;
}
