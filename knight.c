// knight's tour, only gives one solution

#include <stdio.h>

int main(){
		int x=0,y=0,s=0;
		int step[63]={0};
		int move[8][2]={{2,1},{1,2},{-1,2},{-2,1},{-2,-1},{-1,-2},{1,-2},{2,-1}};
		int done[8][8]={{0}};
		int i;
		done[0][0]=1;
		
		printf("Start at (%d,%d)\n", x, y);
		while(s<63){
			if(step[s]==8){
				step[s]=0;
				s--;
				done[x][y]=0;
				x=x-move[step[s]][0];
				y=y-move[step[s]][1];
				step[s]++;
			}else{
				if(x+move[step[s]][0]>=0 && x+move[step[s]][0]<8 && y+move[step[s]][1]>=0 && y+move[step[s]][1]<8 && done[x+move[step[s]][0]][y+move[step[s]][1]]==0){
					x=x+move[step[s]][0];
					y=y+move[step[s]][1];
					done[x][y]=1;
					s++;
				}else{
					step[s]++;
				}
			}
		}
		
		for(i=0;i<63;i++){
			printf("Move %d steps horizontally and %d steps vertically\n", move[step[i]][0], move[step[i]][1]);
		}
		printf("Final Position: (%d,%d)\n", x, y);
		system("pause");
		return 0;
}
