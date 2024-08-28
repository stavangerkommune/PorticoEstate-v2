// @ts-ignore
import {colours} from '../../styles/resource-colours.module.scss'
import {useMemo} from "react";
export const useColours = (): Array<string> | undefined => {
    return useMemo(() => colours.split(', '), []);
}
